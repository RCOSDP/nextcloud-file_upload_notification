<?php

script('file-update-notifications', 'settings');
style('file-update-notifications', 'settings');
?>

<div id="file-update-notifications" class="section">
    <h2 class="inlineblock"><?php p($l->t('File Update Notifications')); ?></h2>
    <p class="settings-hint"></p>
    <div>
        <div>
            <label>
                <span><?php p($l->t('ID')); ?></span>
                <input type="text" name="server" id="server_id" placeholder="Server name" required value>
                <div id="server_id_msg"></div>
            </label>
        </div>
        <div>
            <label>
                <span><?php p($l->t('Destination server')); ?></span>
                <input type="text" name="server" id="destination_url" placeholder="https://srv.example.com/" required value>
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
                <span><?php p($l->t('Encryption secret')); ?></span>
                <div id="encryption_secret"></div>
                <div id="encryption_secret_msg"></div>
            </label>
        </div>
        <div id="submit_buttons">
            <input id="create_secret" type="submit" value="<?php p($l->t('Create secret')); ?>" >
            <input id="save_settings" type="submit" value="<?php p($l->t('Save settings')); ?>" disabled>
        </div>
    </div>
</div>
