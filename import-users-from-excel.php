<?php
/*
Plugin Name: Import Users from Excel
Description: Import users from an Excel file into WordPress, ensuring compatibility with the Digits plugin for mobile-based registration.
Version: 1.0
Author: Mohammad Kazem Qoliyan
*/

// Load Composer's autoloader
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';
} else {
    wp_die('Composer dependencies not installed. Please run "composer install".');
}


function is_phone($phone)
{
    $pattern = '/(\+?98|098|0|0098)?(9\d{9})/';
    return (bool) preg_match($pattern, $phone);
}

// Delete all users except admin for testing purposes
function iufe_delete_all_users_but_admin() {
    $users = get_users([
        'exclude' => [1] // Keep admin (assuming admin has user ID 1)
    ]);
    
    foreach ($users as $user) {
        wp_delete_user($user->ID);
    }
}

// Uncomment the following line when testing
// iufe_delete_all_users_but_admin();


// Normalize phone numbers for Digits plugin compatibility
function normalize_phone_number($phone_number) {
    // Assuming $phone_number is a valid 10 or 11 digit number starting with '9'
    // Return array with normalized phone number for 'digits_phone' and 'digits_phone_no'
    
    // Example normalization (you may need to adjust based on your number format)
    $digits_phone = '+98' . substr($phone_number, -10);  // With country code
    $digits_phone_no = substr($phone_number, -10);  // Without country code

    return [$digits_phone, $digits_phone_no];
}






// Register the submenu under "Tools"
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

// Enqueue scripts for handling the AJAX process
function iufe_enqueue_scripts($hook)
{
    if ($hook !== 'tools_page_import-users-from-excel') {
        return;
    }
    wp_enqueue_script('iufe-script', plugins_url('/iufe-script.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('iufe-script', 'iufe_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('iufe_import_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'iufe_enqueue_scripts');

// Display the upload form
function iufe_import_page()
{
?>
    <div class="wrap">
        <h2>Import Users from Excel</h2>
        <form id="iufe-import-form" enctype="multipart/form-data">
            <input type="file" name="iufe_excel_file" id="iufe_excel_file" accept=".xlsx">
            <input type="submit" value="Upload and Import" class="button-primary">
            <div id="iufe-progress-bar" style="width: 100%; background-color: #f3f3f3;">
                <div id="iufe-progress" style="width: 0%; height: 24px; background-color: #4caf50;"></div>
            </div>
            <div id="iufe-status"></div>
        </form>
    </div>
<?php
}

// Handle the AJAX request
function iufe_handle_ajax()
{
    check_ajax_referer('iufe_import_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.');
    }

    // Process the uploaded Excel file
    if (!empty($_FILES['file']['tmp_name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded_file = wp_handle_upload($_FILES['file'], ['test_form' => false]);

        if (isset($uploaded_file['file'])) {
            $file_path = $uploaded_file['file'];
            iufe_process_excel_file($file_path);
            wp_send_json_success('File uploaded and processed successfully.');
        } else {
            wp_send_json_error('File upload failed.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }
}
add_action('wp_ajax_iufe_import_users', 'iufe_handle_ajax');

// Process the Excel file to import users
function iufe_process_excel_file($file_path) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

    iufe_delete_all_users_but_admin();

    // Load the Excel file
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Loop through each row (starting from row 3)
    for ($i = 3; $i < count($rows); $i++) {
        $display_name = $rows[$i][0];
        $mobile_number = $rows[$i][1];
        $rank = (int) $rows[$i][2];
        $discount = $rank * 10000;

        // Validate the mobile number using the is_phone function
        if (!is_phone($mobile_number)) {
            // If the mobile number is invalid, skip this record
            continue;
        }

        // Normalize the phone number to standardize 'digits_phone' and 'digits_phone_no'
        $arr_phone = normalize_phone_number($mobile_number);

        // Check if user with this mobile number exists
        $user = get_user_by('login', $mobile_number);
        if (!$user) {
            // Register user using Digits plugin's mobile-based registration
            $user_id = wp_create_user($mobile_number, wp_generate_password(), $mobile_number);
            if (!is_wp_error($user_id)) {
                // Update display name and other details
                wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);

                // Set customer club discount
                update_user_meta($user_id, 'customer_club_discount', $discount);

                // Update Digits-specific meta fields
                update_user_meta($user_id, 'digits_phone', $arr_phone[0]);
                update_user_meta($user_id, 'digits_phone_no', $arr_phone[1]);
            }
        } else {
            // Update existing user's discount
            update_user_meta($user->ID, 'customer_club_discount', $discount);
        }
    }
}
