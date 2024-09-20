<?php
/*
Plugin Name: Import Users from Excel
Description: Import users from an Excel file into WordPress, ensuring compatibility with the Digits plugin for mobile-based registration.
Version: 1.2
Author: Mohammad Kazem Gholian
Author URI: https://valiasrcs.com
Plugin URI: https://valiasrcs.com/fa/how-to-transfer-easrp-users
*/

// Add your phone validation function
function is_phone($phone)
{
    $pattern = '/(\+?98|098|0|0098)?(9\d{9})/';
    return (bool) preg_match($pattern, $phone);
}

// Delete all users except admin for testing purposes
function iufe_delete_all_users_but_admin()
{
    $users = get_users([
        'exclude' => [1] // Keep admin (assuming admin has user ID 1)
    ]);

    foreach ($users as $user) {
        wp_delete_user($user->ID);
    }
}

// Uncomment the following line when testing
// iufe_delete_all_users_but_admin();


function iufe_init_process_status()
{
    update_option('iufe_status', '');
}
register_activation_hook(__FILE__, 'iufe_init_process_status');

// Enqueue scripts for AJAX handling and progress bar update
function iufe_enqueue_scripts($hook)
{
    if ($hook !== 'tools_page_import-users-from-excel') {
        return;
    }
    wp_enqueue_script('iufe-script', plugins_url('/iufe-script.js', __FILE__), ['jquery'], null, true);
    wp_enqueue_style('iufe-style', plugins_url('/iufe-style.css', __FILE__));
    wp_localize_script('iufe-script', 'iufe_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('iufe_import_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'iufe_enqueue_scripts');

// Register the submenu under Tools
function iufe_register_menu()
{
    add_submenu_page(
        'tools.php',
        'Import Users from Excel',
        'Import Users from Excel',
        'manage_options',
        'import-users-from-excel',
        'iufe_import_page'
    );
}
add_action('admin_menu', 'iufe_register_menu');

// Display the upload form with progress bar
function iufe_import_page()
{
    $isIdle = get_option('iufe_status') == "progress" ? false : true;
?>
    <div class="wrap iufe-main">
        <h2>Import Users from Excel</h2>
        <form id="iufe-import-form" enctype="multipart/form-data">
            <input type="file" name="iufe_excel_file" id="iufe_excel_file" accept=".xlsx">
            <input type="submit" id="iufe-btn" <?= $isIdle ? "" : "disabled" ?> value="Upload and Import" class="button-primary">
            <div id="iufe-progress-bar" style="width: 100%; background-color: #f3f3f3; margin-top: 10px;">
                <div id="iufe-progress" style="width: 0%; height: 24px; background-color: #4caf50; text-align: center; line-height: 24px; color: white;">0%</div>
            </div>
            <div id="iufe-status"></div>
        </form>
    </div>
<?php
}

// Handle the initial file upload and save it
function iufe_handle_upload()
{
    check_ajax_referer('iufe_import_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.');
    }

    if (!empty($_FILES['file']['tmp_name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded_file = wp_handle_upload($_FILES['file'], ['test_form' => false]);

        if (isset($uploaded_file['file'])) {
            update_option('iufe_uploaded_file', $uploaded_file['file']); // Save file path to option
            update_option('iufe_status', 'progress');
            $rows = iufe_get_excel_rows($uploaded_file['file']);
            wp_send_json_success([
                'total_rows' => count($rows),
                'message' => 'File uploaded successfully. Starting import...'
            ]);
        } else {
            wp_send_json_error('File upload failed.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }
}
add_action('wp_ajax_iufe_upload_file', 'iufe_handle_upload');

// Handle processing the rows in chunks
function iufe_handle_process_chunk()
{
    check_ajax_referer('iufe_import_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.');
    }

    // Get the current chunk index and the size of the chunk from the AJAX request
    $chunk_start = isset($_POST['chunk_start']) ? intval($_POST['chunk_start']) : 3;
    $chunk_size = isset($_POST['chunk_size']) ? intval($_POST['chunk_size']) : 100;
    $file_path = get_option('iufe_uploaded_file');
    $rows = iufe_get_excel_rows($file_path);
    $total_rows = count($rows);

    // Process the current chunk of rows
    for ($i = $chunk_start; $i < $chunk_start + $chunk_size && $i < $total_rows; $i++) {
        iufe_process_row($rows[$i]);
        $percent = floor(($i * 100) / $total_rows);
        update_option("iufe_progress", $percent);
    }

    // Calculate progress percentage
    $progress = (($i - 2) / ($total_rows - 2)) * 100;

    // Check if there are more rows to process
    if ($i < $total_rows) {
        wp_send_json_success([
            'progress' => $progress,
            'next_chunk_start' => $i,
            'message' => "Processed rows " . ($chunk_start - 2) . " to " . ($i - 2) . " of " . ($total_rows - 3)
        ]);
    } else {
        // All rows processed
        update_option('iufe_status', '');
        wp_send_json_success([
            'progress' => 100,
            'message' => 'All rows have been processed!'
        ]);
    }
}
add_action('wp_ajax_iufe_process_chunk', 'iufe_handle_process_chunk');


function iufe_get_progress()
{
    $progress = get_option('iufe_progress');
    wp_send_json_success([
        'progress' => $progress,
    ]);
}

add_action('wp_ajax_iufe_get_progress', 'iufe_get_progress');

// Get the rows from the Excel file
function iufe_get_excel_rows($file_path)
{
    require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $sheet = $spreadsheet->getActiveSheet();
    return $sheet->toArray();
}

// Process a single row
function iufe_process_row($row)
{
    $display_name = $row[0];
    $mobile_number = $row[1];
    $rank = (int) $row[2];
    $discount = $rank * 10000;

    if (!is_phone($mobile_number)) {
        return;
    }

    $arr_phone = normalize_phone_number($mobile_number);

    $user = get_user_by('login', $mobile_number);
    if (!$user) {
        $user_id = wp_create_user($mobile_number, wp_generate_password(), $mobile_number);
        if (!is_wp_error($user_id)) {
            wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
            update_user_meta($user_id, 'customer_club_discount', $discount);
            update_user_meta($user_id, 'digits_phone', $arr_phone[0]);
            update_user_meta($user_id, 'digits_phone_no', $arr_phone[1]);
        }
    } else {
        update_user_meta($user->ID, 'customer_club_discount', $discount);
    }
}

// Normalize phone numbers for Digits plugin compatibility
function normalize_phone_number($phone_number)
{
    $digits_phone = '+98' . substr($phone_number, -10);
    $digits_phone_no = substr($phone_number, -10);

    return [$digits_phone, $digits_phone_no];
}
