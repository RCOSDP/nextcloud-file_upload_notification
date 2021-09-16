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
                <span><?php p($l->t('Connection common key')); ?></span>
                <div id="connection_common_key"></div>
                <div id="connection_common_key_msg"></div>
            </label>
        </div>
        <div id="submit_buttons">
            <input id="create_connection_common_key" type="submit" value="<?php p($l->t('Generate connection common key')); ?>" >
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
  "min_interval": "10" 
}
    </pre>

</div>

<div class="section">
    <h2 class="inlineblock"><?php p($l->t('Example of Receipt Notification')); ?></h2>

    <pre>
import logging
import httplib as http
import hmac
from hashlib import sha256
import json
from flask import request
from framework.exceptions import HTTPError

from my_app import celery_check_updated_files

logger = logging.getLogger(__name__)

### connection common key
SECRET = '?????????????????????'  # from configuration file or DB.

# example of flask
def notif_from_nextcloud():
    SIGNATURE_HEADER = 'X-Nextcloud-File-Upload-Notification-Signature'
    signature = request.headers.get(SIGNATURE_HEADER)
    try:
        data = json.loads(request.data)
        provider_id = data.get('id')
    except Exception:
        logger.error('provider_id not found')
        raise HTTPError(http.FORBIDDEN)

    digest = hmac.new(SECRET.encode(), request.data, sha256).hexdigest()
    if not hmac.compare_digest(signature.encode('utf-8'), digest):
        logger.error('invalid signature')
        raise HTTPError(http.FORBIDDEN)

    since = data.get('since')
    interval = data.get('min_interval')

    # run in background task
    celery_check_updated_files.delay(provider_id, since, interval)

    # return immediately
    return ''
    </pre>

</div>

<div class="section">
    <h2 class="inlineblock"><?php p($l->t('Example of API Call')); ?></h2>

    <pre>
curl -u test2:password -H &#39;OCS-APIRequest: true&#39; -X GET &#39;https://nextcloud.example.com/ocs/v2.php/apps/file_upload_notification/api/recent?since=1607511624&#39;
    </pre>

</div>

<div class="section">

    <h2 class="inlineblock"><?php p($l->t('Example of API Call Response')); ?></h2>

    <pre>
&lt;&#63;xml version=&quot;1.0&quot;&#63;&gt;
&lt;ocs&gt;
 &lt;meta&gt;
  &lt;status&gt;ok&lt;/status&gt;
  &lt;statuscode&gt;200&lt;/statuscode&gt;
  &lt;message&gt;OK&lt;/message&gt;
 &lt;/meta&gt;
 &lt;data&gt;
  &lt;count&gt;2&lt;/count&gt;
  &lt;files&gt;
   &lt;element&gt;
    &lt;id&gt;288&lt;/id&gt;
    &lt;type&gt;file&lt;/type&gt;
    &lt;time&gt;1607512590&lt;/time&gt;
    &lt;name&gt;test1.txt&lt;/name&gt;
    &lt;path&gt;/test2/test1.txt&lt;/path&gt;
    &lt;modified_user&gt;test2&lt;/modified_user&gt;
   &lt;/element&gt;
   &lt;element&gt;
    &lt;id&gt;277&lt;/id&gt;
    &lt;type&gt;file&lt;/type&gt;
    &lt;time&gt;1607511624&lt;/time&gt;
    &lt;name&gt;test2.txt&lt;/name&gt;
    &lt;path&gt;/test2/test2.txt&lt;/path&gt;
    &lt;modified_user&gt;test2&lt;/modified_user&gt;
   &lt;/element&gt;
  &lt;/files&gt;
 &lt;/data&gt;
&lt;/ocs&gt;
    </pre>
</div>