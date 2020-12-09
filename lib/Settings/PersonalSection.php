<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {

        private $l;

        private $url;

        public function __construct(IURLGenerator $url, IL10N $l) {
                $this->url = $url;
                $this->l = $l;
        }

        public function getIcon() {
                return $this->url->imagePath('file_upload_notification', 'changeme.svg');
        }

        public function getID() {
                return 'file_upload_notification';
        }

        public function getName() {
                return $this->l->t('File Upload Notification');
        }

        public function getPriority() {
                return 10;
        }
}