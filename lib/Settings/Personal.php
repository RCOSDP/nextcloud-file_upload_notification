<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Personal implements ISettings {

    private $config;

    private $l;

    public function __construct(IConfig $config, IL10N $l) {
        $this->config = $config;
        $this->l = $l;
    }

    public function getForm() {
        return new TemplateResponse('file-update-notifications', 'personal_settings', []);
    }

    public function getSection() {
        return 'file-update-notifications';
    }

    public function getPriority() {
        return 70;
    }
}
