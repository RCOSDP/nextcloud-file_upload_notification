<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ConfigController extends OCSController {

    private $config;
    private $logger;
    private $userSession;

    public function __construct($appName,
                                IRequest $request,
                                IConfig $config,
                                IUserSession $userSession,
                                LoggerInterface $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->logger = $logger;
        $this->userSession = $userSession;
    }

    /**
     * create encryption secret
     * @NoAdminRequired
     * @param (string) $length - length of secret to be created
     */
    public function createSecret($length = '16') :JSONResponse {
        if (is_null($length) || !is_numeric($length) || intval($length) < 1) {
            $length = '16';
        }

        $data = [];
        $data['secret'] = bin2hex(random_bytes(intval($length)));
        return new JSONResponse($data, Http::STATUS_OK);
    }

    /**
     * save configuration to user based store
     * @NoAdminRequired
     * @param (string) $id - server id
     * @param (string) $url - server url to be notified
     * @param (string) $interval - notification interval
     * @param (string) $secret - secret used to protect notifications
     */
    public function setConfig($id, $url, $interval, $secret) :JSONResponse {
        $data = [];

        if (is_null($id)) {
            $data['error'] = 'id is invalid';
            return new JSONResponse(
                $data,
                Http::STATUS_BAD_REQUEST
            );
        }

        if (is_null($url)) {
            $data['error'] = 'url is invalid';
            return new JSONResponse(
                $data,
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!is_numeric($interval) || intval($interval) < 1) {
            $data['error'] = 'interval is invalid';
            return new JSONResponse(
                $data,
                Http::STATUS_BAD_REQUEST
            );
        }

        if (is_null($secret) || hex2bin($secret) === false) {
            $data['error'] = 'secret is invalid';
            return new JSONResponse(
                $data,
                Http::STATUS_BAD_REQUEST
            );
        }

        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $data = [
            'id' => $id,
            'url' => $url,
            'interval' => $interval,
            'secret' => $secret];
        $json = json_encode($data);
        $this->config->setAppValue($this->appName, $userId, $json);

        return new JSONResponse($data, Http::STATUS_OK);
    }

    /**
     * get configuration from user based store
     * @NoAdminRequired
     */
    public function getConfig() :JSONResponse {
        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $json = $this->config->getAppValue($this->appName, $userId);

        return new JSONResponse(json_decode($json, true), Http::STATUS_OK);
    }

    /**
     * delete configuration from user based store
     * @NoAdminRequired
     */
    public function deleteConfig() :JSONResponse {
        $data = [];
        $user = $this->userSession->getUser();
        $userId = $user->getUID();
        $this->config->deleteAppValue($this->appName, $userId);

        return new JSONResponse($data, Http::STATUS_OK);
    }
}
