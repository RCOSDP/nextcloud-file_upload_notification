<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Db;

use OCP\AppFramework\Db\Entity;

class FileCacheExtended extends Entity {

    protected $fileid;
    protected $metadataEtag;
    protected $creationTime;
    protected $uploadTime;

    public function __constrct() {
        $this->addType('fileid', 'integer');
        $this->addType('creation_time', 'integer');
        $this->addType('upload_time', 'integer');
    }
}