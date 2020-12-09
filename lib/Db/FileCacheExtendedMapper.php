<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class FileCacheExtendedMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'filecache_extended', FileCacheExtended::class);
    }

    public function find(int $fileid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('f.*')
            ->from($this->tableName, 'f')
            ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid, IQueryBuilder::PARAM_INT)));

        try {
            $result = $this->findEntity($qb);
        } catch (DoesNotExistException $e) {
            return null;
        }

        return $result;
    }

    public function findAll(int $limit, int $offset) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('f.*')
            ->from($this->tableName, 'f')
            ->orderBy('f.upload_time', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }
}
