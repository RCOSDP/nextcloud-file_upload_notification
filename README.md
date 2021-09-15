# Nextcloud App: file_upload_notification
Nextcloud application for the file-upload notification from Nextcloud to an external service (GakuNin RDM, etc.).

## System requirements

- Nextcloud 18 or higher

# Function Overview:

Nextcloud App: 
 file_upload_notification
 Nextcloud application for the file-upload notification from Nextcloud to an external service (GakuNin RDM, etc.).

# Usage:

Method: 
 GET
URL: 
 https://<nextcloud_server>/ocs/v2.php/apps/nextcloud-file_upload_notification/api/recent
Query:
 since: set UNIX time to get update history after this time.

## Example of API Call:

curl -u test2:password -H 'OCS-APIRequest: true' -X GET 'https://nextcloud.example.com/ocs/v2.php/apps/file_upload_notification/api/recent?since=1607511624'

## Example of API Call Response:

```
<?xml version="1.0"?>
<ocs>
 <meta>
  <status>ok</status>
  <statuscode>200</statuscode>
  <message>OK</message>
 </meta>
 <data>
  <count>2</count>
  <files>
   <element>
    <id>288</id>
    <type>file</type>
    <time>1607512590</time>
    <name>test1.txt</name>
    <path>/test2/test1.txt</path>
    <modified_user>test2</modified_user>
   </element>
   <element>
    <id>277</id>
    <type>file</type>
    <time>1607511624</time>
    <name>test2.txt</name>
    <path>/test2/test2.txt</path>
    <modified_user>test2</modified_user>
   </element>
  </files>
 </data>
</ocs>
```
