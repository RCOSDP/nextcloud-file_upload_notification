<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserSession;
use OC\Files\View;

use OCA\FileUploadNotification\Db\FileUpdateMapper;
use OCA\FileUploadNotification\Db\FileCacheExtendedMapper;

class RecentController extends OCSController {

    private $config;
    private $rootFolder;
    private $userSession;
    private $updateMapper;
    private $cacheExtendedMapper;
    private $logger;

    public function __construct($appName,
                                IRequest $request,
                                IConfig $config,
                                IRootFolder $rootFolder,
                                IUserSession $userSession,
                                FileUpdateMapper $updateMapper,
                                FileCacheExtendedMapper $cacheExtendedMapper,
                                ILogger $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->updateMapper = $updateMapper;
        $this->cacheExtendedMapper = $cacheExtendedMapper;
        $this->logger = $logger;
    }

    private function sortByUploadTime(array $records) :array {
        $callback = function ($a, $b) :int {
            $timeA = $a->getUploadTime();
            $timeB = $b->getUploadTime();
            if ($timeA == $timeB) {
                return 0;
            }
            return ($timeA < $timeB) ? 1 : -1;
        };
        usort($records, $callback);
        return $records;
    }

    private function getUniqueRecords(array $records) :array {
        $tmp = [];
        $recordCount = count($records);
        for ($i = 0; $i < $recordCount; $i++) {
            $id = $records[$i]->getId();
            if (array_key_exists($id, $tmp) === false || ($tmp[$id]->getUploadTime() < $records[$i]->getUploadTime())) {
                $tmp[$id] = $records[$i];
            }
        }
        return array_values($tmp);
    }

    private function includeRecordsBeforeTime(array $records, int $time) :bool {
        $recordCount = count($records);
        for ($i = 0; $i < $recordCount; $i++) {
            if ($records[$i]->getUploadTime() < $time) {
                return true;
            }
        }

        return false;
    }

    private function recentUploadSearch($limit, $offset) {
        return $this->cacheExtendedMapper->findAll($limit, $offset);
    }

    private function getRecentUpload($folder, $limit, $offset = 0) {
        $searchLimit = 500;
        $results = [];
        $searchResultCount = 0;
        $count = 0;
        do {
            $searchResult = $this->recentUploadSearch($searchLimit, $offset);

            // Exit condition if there are no more results
            if (count($searchResult) === 0) {
                break;
            }

            $searchResultCount += count($searchResult);

            foreach ($searchResult as $result) {
                $files = $folder->getById(intval($result->getFileid()));
                $results = array_merge($results, $files);
            }

            $offset += $searchLimit;
            $count++;
        } while (count($results) < $limit && ($searchResultCount < (3 * $limit) || $count < 5));

        return array_slice($results, 0, $limit);
    }

    private function getRecentRecords(Folder $folder, int $limit, int $since) :array {
        $totalRecords = [];
        for ($offset = 0, $recordCount = $limit, $records = [];
            !$this->includeRecordsBeforeTime($records, $since) && $recordCount === $limit;
            $offset += $limit, $recordCount = count($records)) {
            $records = $this->getRecentUpload($folder, $limit, $offset);
            $totalRecords = array_merge($totalRecords, $records);
        }

        return $totalRecords;
    }

    private function selectRecords(array $records, int $since) :array {
        $results= [];
        for ($i = 0, $recordCount = count($records); $i < $recordCount; $i++) {
            if ($records[$i]->getUploadTime() >= $since) {
                array_push($results, $records[$i]);
            }
        }

        return $results;
    }

    private function constructResponse(array $records, string $userId) {
        $data = [];
        $recordCount = count($records);
        $data['count'] = $recordCount;
        $data['files'] = [];
        $userFolderPrefix = '/' . $userId . '/files';
        for ($i = 0; $i < $recordCount; $i++) {
            $element = [];
            $element['id'] = $records[$i]->getId();
            $element['type'] = $records[$i]->getType() === Folder::TYPE_FILE ? 'file' : 'folder';
            $element['time'] = $records[$i]->getUploadTime();
            $element['name'] = $records[$i]->getName();

            $path = explode($userFolderPrefix, $records[$i]->getPath(), 2);
            $element['path'] = $path[1];

            $mtime = $records[$i]->getMtime();
            $entity = $this->updateMapper->find($element['id'], $mtime);
            if (is_null($entity)) {
                $element['modified_user'] = '';
            } else {
                $element['modified_user'] = $entity->getUser();
            }

            array_push($data['files'], $element);
        }

        return $data;
    }

    /**
     * get a list of files updated after the specified time
     * @NoAdminRequired
     * @param (string) $since - include files updated after this time in the list
     */
    public function getRecent($since) {
        if (is_null($since)) {
            return new DataResponse(
                'query parameter since is missing',
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!is_numeric($since) || intval($since) < 0) {
            return new DataResponse(
                'since is invalid number',
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [];

        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $this->logger->debug('user: ' . $userId);
        $userFolder = $this->rootFolder->getUserFolder($userId);

        /*
         * get recoreds up to specified value by since
         */
        $totalRecords = $this->getRecentRecords($userFolder, 1000, intval($since));
        $totalRecords = $this->sortByUploadTime($totalRecords);
        $totalRecords = $this->getUniqueRecords($totalRecords);

        if (count($totalRecords) === 0) {
            $data['count'] = 0;
            $data['files'] = [];
            return new DataResponse(
                $data,
                Http::STATUS_OK
            );
        }

        /*
         * get records updated during the acquisition process
         */
        $latestTime = $totalRecords[0]->getUploadtime();
        $additionalRecords = $this->getRecentRecords($userFolder, 100, $latestTime);
        $totalRecords = array_merge($additionalRecords, $totalRecords);
        $totalRecords = $this->sortByUploadTime($totalRecords);
        $totalRecords = $this->getUniqueRecords($totalRecords);
        $totalRecords = $this->selectRecords($totalRecords, intval($since));
        $data = $this->constructResponse($totalRecords, $userId);

        /*
         * set since value for hook function
         */
        $sinceKey = $userId . '#since';
        $this->config->setAppValue($this->appName, $sinceKey, $totalRecords[0]->getUploadTime());
        $this->logger->info('set since: ' . strval($totalRecords[0]->getUploadTime()));

        return new DataResponse(
            $data,
            Http::STATUS_OK
        );
    }
}