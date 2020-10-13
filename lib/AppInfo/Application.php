<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\AppInfo;

use OCP\AppFramework\App;

use OCA\FileUpdateNotifications\Db\FileUpdateMapper;
use OCA\FileUpdateNotifications\Hooks\UserHooks;

class Application extends App {
    public const APP_ID = 'file-update-notifications';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService('UserHooks', function() {
            $container = $this->getContainer();
            $server = $container->getServer();
            return new UserHooks(
                self::APP_ID,
                $server->getConfig(),
                $server->getRootFolder(),
                new FileUpdateMapper($server->getDatabaseConnection()),
                $server->getLogger()
            );
        });
    }

    public function register() {
        $this->registerHooks();
    }

    public function registerHooks() {
        $container = $this->getContainer();
        $container->query('UserHooks')->register();
    }
}