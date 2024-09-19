jQuery(document).ready(function($) {
    $('#iufe-import-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append('file', $('#iufe_excel_file')[0].files[0]);
        formData.append('action', 'iufe_import_users');
        formData.append('nonce', iufe_ajax.nonce);

        // Reset progress bar
        $('#iufe-progress').css('width', '0%').text('0%');

        $.ajax({
            url: iufe_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        $('#iufe-progress').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $('#iufe-progress').css('width', response.data.progress + '%').text(Math.round(response.data.progress) + '%');
                    $('#iufe-status').html('<p>' + response.data.message + '</p>');
                } else {
                    $('#iufe-status').html('<p>Error: ' + response.data + '</p>');
                }
            }
        });
    });
});
