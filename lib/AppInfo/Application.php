<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
    public const APP_ID = 'file-update-notifications';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }
}