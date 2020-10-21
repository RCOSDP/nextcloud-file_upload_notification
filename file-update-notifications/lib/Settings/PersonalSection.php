<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\Settings;

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
                return $this->url->imagePath('file-update-notifications', 'changeme.svg');
        }

        public function getID() {
                return 'file-update-notifications';
        }

        public function getName() {
                return $this->l->t('File Update Notifications');
        }

        public function getPriority() {
                return 10;
        }
}