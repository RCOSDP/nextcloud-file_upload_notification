<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Db;

use OCP\AppFramework\Db\Entity;

class FileUpdate extends Entity {

    protected $fileid;
    protected $mtime;
    protected $user;

    public function __constrct() {
        $this->addType('fileid', 'integer');
        $this->addType('mtime', 'integer');
    }
}