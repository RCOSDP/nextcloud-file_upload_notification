$(document).ready(function() {
        var id_exists = false;
        var url_exists = false;
        var interval_exists = false;
        var secret_exists = false;

        var switchSaveButton = function() {
                if (id_exists === true &&
                    url_exists === true &&
                    interval_exists === true &&
                    secret_exists === true) {
                        $('#save_settings').prop('disabled', false);
                } else {
                        $('#save_settings').prop('disabled', true);
                }
        };

        var url = OC.generateUrl('/apps/file_upload_notification/config');
        $.get(url, function(response) {
                if (response === null || response === undefined || response.length === 0) {
                        return;
                }

                if ('id' in response) {
                        if (response['id'].length > 0) {
                                $('#server_id').val(response['id']);
                                id_exists = true;
                        }
                }

                if ('url' in response) {
                        if (response['url'].length > 0) {
                                $('#destination_url').val(response['url']);
                                url_exists = true;
                        }
                }

                if ('interval' in response) {
                        if (response['interval'].length > 0) {
                                $('#notification_interval').val(response['interval']);
                                interval_exists = true;
                        }
                }

                if ('secret' in response) {
                        if (response['secret'].length > 0) {
                                $('#encryption_secret').text(response['secret']);
                                secret_exists = true;
                        }
                }
                switchSaveButton();
        });

        $('#server_id').change(function() {
                var value = $(this).val();
                if (value === undefined || value === null || value.length === 0) {
                        $('#server_id_msg').text('Invalid parameter');
                        id_exists = false;
                } else {
                        $('#server_id_msg').text('');
                        id_exists = true;
                }
                switchSaveButton();
        });

        $('#destination_url').change(function() {
                var value = $(this).val();
                if (value === undefined || value === null || value.length === 0) {
                        $('#destination_url_msg').text('Invalid parameter');
                        url_exists = false;
                } else {
                        $('#destination_url_msg').text('');
                        url_exists = true;
                }
                switchSaveButton();
        });

        $('#notification_interval').change(function() {
                var value = $(this).val();
                if (value === undefined || value === null || isNaN(Number(value)) || Number(value) < 1) {
                        $('#notification_interval_msg').text('Invalid parameter');
                        interval_exists = false;
                } else {
                        $('#notification_interval_msg').text('');
                        interval_exists = true;
                }
                switchSaveButton();
        });

        $('#create_secret').click(function() {
                var url = OC.generateUrl('/apps/file_upload_notification/secret');
                var params = {length: 16};
                $.get(url, params, function(response) {
                        $('#encryption_secret').text(response['secret']);
                        $('#encryption_secret_msg').text('');
                        secret_exists = true;
                        switchSaveButton();
                });
        });

        $('#save_settings').click(function() {
                var execute = true;

                if (id_exists === false) {
                        $('#server_id_msg').text('Invalid parameter');
                        execute = false;
                } else {
                        $('#server_id_msg').text('');
                }

                if (url_exists === false) {
                        $('#destination_url_msg').text('Invalid parameter');
                        execute = false;
                } else {
                        $('#destination_url_msg').text('');
                }

                if (interval_exists === false) {
                        $('#notification_interval_msg').text('Invalid parameter');
                        execute = false;
                } else {
                        $('#notification_interval_msg').text('');
                }

                if (secret_exists === false) {
                        $('#encryption_secret_msg').text('Missin encryption secret');
                        execute = false;
                } else {
                        $('#encryption_secret_msg').text('');
                }

                if (execute === false) {
                        return;
                }

                var url = OC.generateUrl('/apps/file_upload_notification/config');
                var params = {
                        id: $('#server_id').val(),
                        url: $('#destination_url').val(),
                        interval: $('#notification_interval').val(),
                        secret: $('#encryption_secret').text()
                };
                $.post(url, params)
                .done(function(response) {
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                        $json = $.parseJSON(jqXHR.responseText);
                        alert($json['error']);
                });
        });
});
