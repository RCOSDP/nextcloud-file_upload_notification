<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\Hooks;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OC\Files\Node\Node;

use OCA\FileUpdateNotifications\Db\FileUpdate;
use OCA\FileUpdateNotifications\Db\FileUpdateMapper;

class UserHooks {

    private $appName;
    private $config;
    private $rootFolder;
    private $mapper;
    private $logger;
    private $grdmUriPath = '/api/v1/addons/nextcloudinstitutions/webhook/';

    public function __construct($appName,
                                IConfig $config,
                                IRootFolder $rootFolder, 
                                FileUpdateMapper $mapper,
                                ILogger $logger) {
        $this->appName = $appName;
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    public function saveRecord(int $fileid, int $mtime, string $user) :FileUpdate {
        $entity = new FileUpdate();
        $entity->setFileid($fileid);
        $entity->setMtime($mtime);
        $entity->setUser($user);
        $result = $this->mapper->insert($entity);

        return $result;
    }

    public function register() {
        $notification_callback = function(Node $node) {
            $dateTime = new \DateTime();
            $eventTime = $dateTime->getTimestamp();
            $this->logger->debug('time: ' . strval($eventTime));

            $userSession = \OC::$server->getUserSession();
            $user = $userSession->getUser();
            $userId = $user->getUID();
            $this->logger->debug('updater: ' . $userId);

            /*
             * save this record(fileid, mtime, user) to this application's table
             */
            $fileid = $node->getId();
            $mtime = $node->getMtime();
            $this->logger->debug('fileid: ' . strval($fileid));
            $this->logger->debug('mtime: ' . strval($mtime));
            $entity = $this->saveRecord($fileid, $mtime, $userId);

            /*
             * get owner's settings
             */
            $owner = $node->getOwner();
            $ownerId = $owner->getUID();
            $this->logger->debug('owner of the file: ' . $ownerId);
            $json = $this->config->getAppValue($this->appName, $ownerId);
            if (strlen($json) === 0) {
                $this->logger->error('settings is empty.');
                return;
            }

            $userConfig = json_decode($json, true);
            $base_url = $userConfig['url'];
            $interval = $userConfig['interval'];
            $secret = $userConfig['secret'];

            /*
             * check previous notification time
             *
             * a key that relates to previous notification time never conflicts with user identifier
             * because the key use sharp sign that is not used for user identifier.
             * see https://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap03.html#tag_03_283
             * for details.
             */
            $prevTimeKey = $ownerId . '#previous_time';
            $previousTime = $this->config->getAppValue($this->appName, $prevTimeKey);
            if (strlen($previousTime) !== 0) {
                $this->logger->debug('previous time: ' . strval($previousTime));
                if (($eventTime - intval($previousTime)) < intval($interval)) {
                    $this->logger->debug('suppress notification');
                    return;
                }
            }
            $this->logger->info('send a notification');
            $this->config->setAppValue($this->appName, $prevTimeKey, $eventTime);

            /*
             * check since value
             */
            $sinceKey = $ownerId . '#since';
            $since = $this->config->getAppValue($this->appName, $sinceKey);
            if (strlen($since) === 0) {
                $since = '0';
            }
            $this->logger->info('since: ' . $since);

            /*
             * connect to the server
             */
            $url = $base_url . $this->grdmUriPath;
            $this->logger->debug('url: ' . $url);

            $postbody = [
                'min_interval' => $interval,
                'since' => $since
            ];
            $response = json_encode($postbody);
            $hash = hash_hmac("sha256", $json, $secret);
            $http_opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-type: application/json',
                        'X-Nextcloud-File-Update-Notifications-Signature: ' . $hash
                    ],
                    'content' => $response
                ]
            ];
            $context = stream_context_create($http_opts);
            $contents = file_get_contents($url, false, $context);
        };
        $this->rootFolder->listen('\OC\Files', 'postWrite', $notification_callback);

        $cleanup_callback = function(Node $node) {
            $fileid = $node->getId();
            $entities = $this->mapper->findAll($fileid);
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    $this->mapper->delete($entity);
                }
                $this->logger->info('delete all records related to the file.');
            }
        };
        $this->rootFolder->listen('\OC\Files', 'preDelete', $cleanup_callback);
    }
}