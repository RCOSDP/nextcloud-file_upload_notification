<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Settings;

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
        return new TemplateResponse('file_upload_notification', 'personal_settings', []);
    }

    public function getSection() {
        return 'file_upload_notification';
    }

    public function getPriority() {
        return 70;
    }
}
