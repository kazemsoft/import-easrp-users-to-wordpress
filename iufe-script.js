jQuery(document).ready(function($) {
    $('#iufe-import-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append('file', $('#iufe_excel_file')[0].files[0]);
        formData.append('action', 'iufe_upload_file');
        formData.append('nonce', iufe_ajax.nonce);

        // Reset progress bar
        $('#iufe-progress').css('width', '0%').text('0%');
        $('#iufe-status').html('');

        // Upload the file first
        $.ajax({
            url: iufe_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Start processing rows after the file is uploaded
                    processNextRow(3, response.data.total_rows); // Start from row 3
                } else {
                    $('#iufe-status').html('<p>Error: ' + response.data + '</p>');
                }
            }
        });
    });

    function processNextRow(currentRow, totalRows) {
        $.ajax({
            url: iufe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iufe_process_row',
                nonce: iufe_ajax.nonce,
                row_index: currentRow
            },
            success: function(response) {
                if (response.success) {
                    var progress = response.data.progress;
                    $('#iufe-progress').css('width', progress + '%').text(Math.round(progress) + '%');
                    $('#iufe-status').html('<p>' + response.data.message + '</p>');

                    if (progress < 100) {
                        processNextRow(response.data.row_index, totalRows);
                    }
                } else {
                    $('#iufe-status').html('<p>Error: ' + response.data + '</p>');
                }
            }
        });
    }
});
