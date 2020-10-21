<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\Tests\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;

use OCA\FileUpdateNotifications\Controller\ConfigController;

class ConfigControllerTest extends \Test\TestCase {

    private $request;
    private $config;
    private $user;
    private $userSession;
    private $logger;

    protected function setUp() :void {
        parent::setUp();

        $this->request = $this->getMockBuilder(IRequest::class)->getMock();

        $this->config = $this->getMockBuilder(IConfig::class)->getMock();

        $this->user = $this->getMockBuilder(IUser::class)->getMock();
        $this->user->method('getUID')->willReturn('userid');

        $this->userSession = $this->getMockBuilder(IUserSession::class)->disableOriginalConstructor()->getMock();
        $this->userSession->method('getUser')->willReturn($this->user);

        $this->logger = $this->getMockBuilder(ILogger::class)->getMock();
    }

    protected function tearDown() :void {
        parent::tearDown();
    }

    public function testCreateSecret() :void {
        $expected_status = Http::STATUS_OK;

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $expected_length1 = 32;
        $result1 = $controller->createSecret();
        $this->assertEquals($expected_status, $result1->getStatus());
        $this->assertEquals($expected_length1, strlen($result1->getData()['secret']));

        $expected_length2 = 64;
        $result2 = $controller->createSecret('32');
        $this->assertEquals($expected_status, $result2->getStatus());
        $this->assertEquals($expected_length2, strlen($result2->getData()['secret']));

        $expected_length3 = 32;
        $result3 = $controller->createSecret('0');
        $this->assertEquals($expected_status, $result3->getStatus());
        $this->assertEquals($expected_length3, strlen($result3->getData()['secret']));

        $expected_length4 = 2;
        $result4 = $controller->createSecret('1');
        $this->assertEquals($expected_status, $result4->getStatus());
        $this->assertEquals($expected_length4, strlen($result4->getData()['secret']));

        $expected_length5 = 32;
        $result5 = $controller->createSecret(null);
        $this->assertEquals($expected_status, $result5->getStatus());
        $this->assertEquals($expected_length5, strlen($result5->getData()['secret']));

        $expected_length6 = 32;
        $result6 = $controller->createSecret('aaaa');
        $this->assertEquals($expected_status, $result6->getStatus());
        $this->assertEquals($expected_length6, strlen($result6->getData()['secret']));

        $expected_length7 = 32;
        $result7 = $controller->createSecret('-1');
        $this->assertEquals($expected_status, $result7->getStatus());
        $this->assertEquals($expected_length7, strlen($result7->getData()['secret']));
    }

    public function testSetConfig() :void {
        $url = 'https://www.example.ac.jp/';
        $interval = '15';
        $secret = '4fdab23178d6c424196c9448070d3bef';

        $expected_status = Http::STATUS_OK;
        $expected_data = [
            'url' => $url,
            'interval' => $interval,
            'secret' => $secret
        ];

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result = $controller->setConfig($url, $interval, $secret);
        $this->assertEquals($expected_status, $result->getStatus());
        $this->assertEquals($expected_data, $result->getData());
    }

    public function testSetConfigArgumentUrlIsNull() :void {
        $url = null;
        $interval = '15';
        $secret = '4fdab23178d6c424196c9448070d3bef';

        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = [
            'error' => 'url is invalid'
        ];

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result = $controller->setConfig($url, $interval, $secret);
        $this->assertEquals($expected_status, $result->getStatus());
        $this->assertEquals($expected_data, $result->getData());
    }

    public function testSetConfigArgumentIntervalIsNull() :void {
        $url = 'https://www.example.ac.jp/';
        $interval = null;
        $secret = '4fdab23178d6c424196c9448070d3bef';

        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = [
            'error' => 'interval is invalid'
        ];

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result = $controller->setConfig($url, $interval, $secret);
        $this->assertEquals($expected_status, $result->getStatus());
        $this->assertEquals($expected_data, $result->getData());
    }

    public function testSetConfigArgumentIntervalIsInvalid() :void {
        $url = 'https://www.example.ac.jp/';
        $secret = '4fdab23178d6c424196c9448070d3bef';

        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = [
            'error' => 'interval is invalid'
        ];

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result1 = $controller->setConfig($url, 'aaaa', $secret);
        $this->assertEquals($expected_status, $result1->getStatus());
        $this->assertEquals($expected_data, $result1->getData());

        $result2 = $controller->setConfig($url, '0', $secret);
        $this->assertEquals($expected_status, $result2->getStatus());
        $this->assertEquals($expected_data, $result2->getData());

        $result3 = $controller->setConfig($url, '-1', $secret);
        $this->assertEquals($expected_status, $result3->getStatus());
        $this->assertEquals($expected_data, $result3->getData());
    }

    public function testSetConfigArgumentSecretIsNull() :void {
        $url = 'https://www.example.ac.jp/';
        $interval = '10';
        $secret = null;

        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = [
            'error' => 'secret is invalid'
        ];

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result = $controller->setConfig($url, $interval, $secret);
        $this->assertEquals($expected_status, $result->getStatus());
        $this->assertEquals($expected_data, $result->getData());
    }

    public function testSetConfigArgumentSecretIsInvalid() :void {
        $url = 'https://www.example.ac.jp/';
        $interval = '10';

        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = [
            'error' => 'secret is invalid'
        ];
        $this->expectWarning();

        $controller = new ConfigController('file-update-notifications',
            $this->request,
            $this->config,
            $this->userSession,
            $this->logger
        );

        $result1 = $controller->setConfig($url, $interval, 'aaa');
        $this->assertEquals($expected_status, $result1->getStatus());
        $this->assertEquals($expected_data, $result1->getData());

        $result2 = $controller->setConfig($url, $interval, 'zzzzzzzz');
        $this->assertEquals($expected_status, $result2->getStatus());
        $this->assertEquals($expected_data, $result2->getData());
    }
}