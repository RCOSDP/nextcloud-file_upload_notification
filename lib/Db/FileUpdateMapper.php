<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class FileUpdateMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'file_upload_notif', FileUpdate::class);
    }

    public function find(int $fileid, int $mtime) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('mtime', $qb->createNamedParameter($mtime, IQueryBuilder::PARAM_INT)));

        try {
            $result = $this->findEntity($qb);
        } catch (DoesNotExistException $e) {
            return null;
        }

        return $result;
    }

    public function findAll(int $fileid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid, IQueryBuilder::PARAM_INT)));

        return $this->findEntities($qb);
    }
}
