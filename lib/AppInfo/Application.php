<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\AppInfo;

use OCP\AppFramework\App;

use OCA\FileUploadNotification\Db\FileUpdateMapper;
use OCA\FileUploadNotification\Hooks\UserHooks;

class Application extends App {
    public const APP_ID = 'file_upload_notification';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService('UserHooks', function($c) {
            return new UserHooks(
                self::APP_ID,
                $c->get('ServerContainer')->getConfig(),
                $c->get('ServerContainer')->getRootFolder(),
                new FileUpdateMapper($c->get('ServerContainer')->getDatabaseConnection()),
                $c->get('ServerContainer')->getLogger()
            );
        });
    }

    public function register() {
        $this->registerHooks();
    }

    public function registerHooks() {
        $container = $this->getContainer();
        $container->get('UserHooks')->register();
    }
}