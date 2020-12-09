<?php

script('file_upload_notification', 'settings');
style('file_upload_notification', 'settings');
?>

<div id="file_upload_notification" class="section">
    <h2 class="inlineblock"><?php p($l->t('File Upload Notification')); ?></h2>
    <p class="settings-hint"></p>
    <div>
        <div>
            <label>
                <span><?php p($l->t('Connection URL')); ?></span>
                <input type="text" name="server" id="destination_url" placeholder="https://yourapp.example.com/path/to/receiver/" required value>
                <div id="destination_url_msg"></div>
            </label>
        </div>
        <div>
            <label>
                <span><?php p($l->t('Notification interval')); ?></span>
                <input type="text" name="interval" id="notification_interval" placeholder="10" required value>
                <div id="notification_interval_msg"></div>
            </label>
        </div>
        <div>
            <label>
                <span><?php p($l->t('Connection ID')); ?></span>
                <input type="text" name="server" id="server_id" placeholder="For the recipient to recognize this sender." required value>
                <div id="server_id_msg"></div>
            </label>
        </div>
        <div>
            <label>
                <span><?php p($l->t('Connection secret')); ?></span>
                <div id="encryption_secret"></div>
                <div id="encryption_secret_msg"></div>
            </label>
        </div>
        <div id="submit_buttons">
            <input id="create_secret" type="submit" value="<?php p($l->t('Generate secret')); ?>" >
            <input id="save_settings" type="submit" value="<?php p($l->t('Save settings')); ?>" disabled>
        </div>
    </div>

</div>

<div class="section">
    <h2 class="inlineblock"><?php p($l->t('Example of Notification')); ?></h2>

    <pre>
{   
  "id": "https://nextcloud.example.com:user001",
  "since": "1603621635",
  "interval": "10" 
}
    </pre>

</div>