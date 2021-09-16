<?php

declare(strict_types=1);

use OCA\FileUploadNotification\AppInfo\Application;

$app = \OC::$server->query(Application::class);
$app->register();