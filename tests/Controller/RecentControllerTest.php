<?php

declare(strict_types=1);

namespace OCA\FileUploadNotification\Tests\Controller;

use OC\Files\Node\Root;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

use OCA\FileUploadNotification\Controller\RecentController;
use OCA\FileUploadNotification\Db\FileUpdateMapper;
use OCA\FileUploadNotification\Db\FileUpdate;
use OCA\FileUploadNotification\Db\FileCacheExtendedMapper;
use OCA\FileUploadNotification\Db\FileCacheExtended;

class RecentControllerTest extends \Test\TestCase {

    private $request;
    private $config;
    private $userFolder;
    private $rootFolder;
    private $user;
    private $userSession;
    private $fileUpdateMapper;
    private $cacheExtendedMapper;
    private $logger;

    protected function setUp() :void {
        parent::setUp();

        $this->request = $this->getMockBuilder(IRequest::class)->getMock();

        $this->config = $this->getMockBuilder(IConfig::class)->getMock();

        $this->userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();

        $this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();

        $this->user = $this->getMockBuilder(IUser::class)->getMock();
        $this->user->method('getUID')->willReturn('userid');

        $this->userSession = $this->getMockBuilder(IUserSession::class)->disableOriginalConstructor()->getMock();
        $this->userSession->method('getUser')->willReturn($this->user);

        $this->fileUpdateMapper = $this->getMockBuilder(FileUpdateMapper::class)->disableOriginalConstructor()->getMock();

        $this->cacheExtendedMapper = $this->getMockBuilder(FileCacheExtendedMapper::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    protected function tearDown() :void {
        parent::tearDown();
    }

    private function doMethod(RecentController $controller, string $methodName, array $params) {
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($controller, $params);
    }

    public function testSortByUploadTime() {
        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getUploadTime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getUploadTime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getUploadTime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getUploadTime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getUploadTime')->willReturn(5);

        $expected = [$file5, $file4, $file3, $file2, $file1];

        $params1 = [[$file1, $file2, $file3, $file4, $file5]];
        $result1 = $this->doMethod($controller, 'sortByUploadTime', $params1);
        $this->assertEquals($result1, $expected);

        $params2 = [[$file5, $file4, $file3, $file2, $file1]];
        $result2 = $this->doMethod($controller, 'sortByUploadTime', $params1);
        $this->assertEquals($result2, $expected);

        $params3 = [[$file4, $file2, $file5, $file3, $file1]];
        $result3 = $this->doMethod($controller, 'sortByUploadTime', $params1);
        $this->assertEquals($result3, $expected);
    }

    public function testIncludeRecordsBeforeTime() {
        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getUploadTime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getUploadTime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getUploadTime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getUploadTime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getUploadTime')->willReturn(5);

        $params1 = [[$file1, $file2, $file3, $file4, $file5], 6];
        $result1 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params1);
        $this->assertEquals($result1, true);

        $params2 = [[$file1, $file2, $file3, $file4, $file5], 5];
        $result2 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params2);
        $this->assertEquals($result2, true);

        $params3 = [[$file1, $file2, $file3, $file4, $file5], 4];
        $result3 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params3);
        $this->assertEquals($result3, true);

        $params4 = [[$file1, $file2, $file3, $file4, $file5], 3];
        $result4 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params4);
        $this->assertEquals($result4, true);

        $params5 = [[$file1, $file2, $file3, $file4, $file5], 2];
        $result5 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params5);
        $this->assertEquals($result5, true);

        $params6 = [[$file1, $file2, $file3, $file4, $file5], 1];
        $result6 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params6);
        $this->assertEquals($result6, false);

        $params7 = [[$file4, $file5], 5];
        $result7 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params7);
        $this->assertEquals($result7, true);

        $params8 = [[$file4, $file5], 4];
        $result8 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params8);
        $this->assertEquals($result8, false);

        $params9 = [[$file4, $file5], 3];
        $result9 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params9);
        $this->assertEquals($result9, false);

        $params10 = [[$file5], 5];
        $result10 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params10);
        $this->assertEquals($result10, false);
    }

    public function testIncludeRecordsBeforeTimeWithEmtpyArray() {
        $expected_data = false;

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $argArray = [];

        $params1 = [$argArray, 0];
        $result1 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params1);
        $this->assertEquals($expected_data, $result1);

        $params2 = [$argArray, 1];
        $result2 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params2);
        $this->assertEquals($expected_data, $result2);

        $params3 = [$argArray, 2];
        $result3 = $this->doMethod($controller, 'includeRecordsBeforeTime', $params3);
        $this->assertEquals($expected_data, $result3);
    }

    public function testGetRecentRecords() {
        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getUploadTime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getUploadTime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getUploadTime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getUploadTime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getUploadTime')->willReturn(5);

        $cacheExtended1 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended1->method('getFileid')->willReturn(1);

        $cacheExtended2 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended2->method('getFileid')->willReturn(2);

        $cacheExtended3 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended3->method('getFileid')->willReturn(3);

        $cacheExtended4 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended4->method('getFileid')->willReturn(4);

        $cacheExtended5 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended5->method('getFileid')->willReturn(5);

        $cacheExtendedMapper1 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper1
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller1 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper1,
            $this->logger
        );

        $map = [
            [1, [$file1]],
            [2, [$file2]],
            [3, [$file3]],
            [4, [$file4]],
            [5, [$file5]]
        ];

        $userFolder1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder1
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params1 = [$userFolder1, 5, 0];
        $result1 = $this->doMethod($controller1, 'getRecentRecords', $params1);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result1);

        $cacheExtendedMapper2 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper2
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller2 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper2,
            $this->logger
        );

        $userFolder2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder2
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params2 = [$userFolder2, 5, 1];
        $result2 = $this->doMethod($controller2, 'getRecentRecords', $params2);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result2);

        $cacheExtendedMapper3 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper3
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller3 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper3,
            $this->logger
        );

        $userFolder3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder3
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params3 = [$userFolder3, 5, 2];
        $result3 = $this->doMethod($controller3, 'getRecentRecords', $params3);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result3);

        $cacheExtendedMapper4 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper4
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller4 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper4,
            $this->logger
        );

        $userFolder4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder4
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params4 = [$userFolder4, 5, 3];
        $result4 = $this->doMethod($controller4, 'getRecentRecords', $params4);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result4);

        $cacheExtendedMapper5 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper5
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller5 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper5,
            $this->logger
        );

        $userFolder5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder5
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params5 = [$userFolder5, 5, 4];
        $result5 = $this->doMethod($controller5, 'getRecentRecords', $params5);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result5);

        $cacheExtendedMapper6 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper6
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 5])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                []
            );

        $controller6 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper6,
            $this->logger
        );

        $userFolder6 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder6
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params6 = [$userFolder6, 5, 5];
        $result6 = $this->doMethod($controller6, 'getRecentRecords', $params6);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result6);

        $cacheExtendedMapper7 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper7
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 3], [500, 503])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended2, $cacheExtended1],
                []
            );

        $controller7 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper7,
            $this->logger
        );

        $userFolder7 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder7
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params7 = [$userFolder7, 3, 0];
        $result7 = $this->doMethod($controller7, 'getRecentRecords', $params7);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result7);

        $cacheExtendedMapper8 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper8
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 3], [500, 503])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended2, $cacheExtended1],
                []
            );

        $controller8 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper8,
            $this->logger
        );

        $userFolder8 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder8
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params8 = [$userFolder8, 3, 1];
        $result8 = $this->doMethod($controller8, 'getRecentRecords', $params8);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result8);

        $cacheExtendedMapper9 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper9
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 3], [500, 503])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended2, $cacheExtended1],
                []
            );

        $controller9 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper9,
            $this->logger
        );

        $userFolder9 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder9
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params9 = [$userFolder9, 3, 2];
        $result9 = $this->doMethod($controller9, 'getRecentRecords', $params9);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result9);

        $cacheExtendedMapper10 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper10
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 3], [500, 503])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended2, $cacheExtended1],
                []
            );

        $controller10 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper10,
            $this->logger
        );

        $userFolder10 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder10
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params10 = [$userFolder10, 3, 3];
        $result10= $this->doMethod($controller10, 'getRecentRecords', $params10);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result10);

        $cacheExtendedMapper11 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper11
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 3], [500, 6])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended2, $cacheExtended1],
                []
            );

        $controller11 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper11,
            $this->logger
        );

        $userFolder11 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder11
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params11 = [$userFolder11, 3, 4];
        $result11 = $this->doMethod($controller11, 'getRecentRecords', $params11);
        $this->assertEquals([$file5, $file4, $file3], $result11);

        $cacheExtendedMapper12 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper12
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 2], [500, 4], [500, 504])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended1],
                []
            );

        $controller12 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper12,
            $this->logger
        );

        $userFolder12 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder12
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params12 = [$userFolder12, 2, 0];
        $result12 = $this->doMethod($controller12, 'getRecentRecords', $params12);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result12);

        $cacheExtendedMapper13 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper13
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 2], [500, 4], [500, 504])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended1],
                []
            );

        $controller13 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper13,
            $this->logger
        );

        $userFolder13 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder13
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params13 = [$userFolder13, 2, 1];
        $result13 = $this->doMethod($controller13, 'getRecentRecords', $params13);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result13);

        $cacheExtendedMapper14 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper14
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 2], [500, 4], [500, 504])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended1],
                []
            );

        $controller14 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper14,
            $this->logger
        );

        $userFolder14 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder14
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params14 = [$userFolder14, 2, 2];
        $result14 = $this->doMethod($controller14, 'getRecentRecords', $params14);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result14);

        $cacheExtendedMapper15 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper15
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 2])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1]
            );

        $controller15 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper15,
            $this->logger
        );

        $userFolder15 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder15
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params15 = [$userFolder15, 2, 3];
        $result15 = $this->doMethod($controller15, 'getRecentRecords', $params15);
        $this->assertEquals([$file5, $file4, $file3, $file2], $result15);

        $cacheExtendedMapper16 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper16
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 2])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1]
            );

        $controller16 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper16,
            $this->logger
        );

        $userFolder16 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder16
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params16 = [$userFolder16, 2, 4];
        $result16 = $this->doMethod($controller16, 'getRecentRecords', $params16);
        $this->assertEquals([$file5, $file4, $file3, $file2], $result16);

        $cacheExtendedMapper17 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper17
            ->method('findAll')
            ->withConsecutive([500, 0])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1]
            );

        $controller17 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper17,
            $this->logger
        );

        $userFolder17 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder17
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params17 = [$userFolder17, 2, 5];
        $result17 = $this->doMethod($controller17, 'getRecentRecords', $params17);
        $this->assertEquals([$file5, $file4], $result17);

        $cacheExtendedMapper18 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper18
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 1])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1]
            );

        $controller18 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper18,
            $this->logger
        );

        $userFolder18 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder18
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params18 = [$userFolder18, 1, 5];
        $result18 = $this->doMethod($controller18, 'getRecentRecords', $params18);
        $this->assertEquals([$file5, $file4], $result18);



        $cacheExtendedMapper19 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper19
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 1], [500, 2])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [$cacheExtended3, $cacheExtended2, $cacheExtended1]
            );

        $controller19 = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $cacheExtendedMapper19,
            $this->logger
        );

        $userFolder19 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder19
            ->method('getById')
            ->will($this->returnValueMap($map));
        $params19 = [$userFolder19, 1, 4];
        $result19 = $this->doMethod($controller19, 'getRecentRecords', $params19);
        $this->assertEquals([$file5, $file4, $file3], $result19);
    }

    public function testGetUniqueRecords() :void {
        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getUploadTime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getUploadTime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getUploadTime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getUploadTime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getUploadTime')->willReturn(5);

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $params1 = [[$file5, $file4, $file3, $file2, $file1]];
        $result1 = $this->doMethod($controller, 'getUniqueRecords', $params1);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result1);

        $params2 = [[$file3, $file5, $file1, $file2, $file4]];
        $result2 = $this->doMethod($controller, 'getUniqueRecords', $params2);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result2);

        $params3 = [[$file3, $file1, $file5, $file3, $file1, $file2, $file4, $file5, $file5]];
        $result3 = $this->doMethod($controller, 'getUniqueRecords', $params3);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result3);
    }

    public function testGetUniqueRecordsWithEmptyArray() :void {
        $expected_data = [];

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $argArray = [];
        $params = [$argArray];

        $result = $this->doMethod($controller, 'getUniqueRecords', $params);
        $this->assertEquals($expected_data, $result);
    }

    public function testSelectRecords() :void {
        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getUploadTime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getUploadTime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getUploadTime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getUploadTime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getUploadTime')->willReturn(5);

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $params1 = [[$file5, $file4, $file3, $file2, $file1], 0];
        $result1 = $this->doMethod($controller, 'selectRecords', $params1);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result1);

        $params2 = [[$file5, $file4, $file3, $file2, $file1], 1];
        $result2 = $this->doMethod($controller, 'selectRecords', $params2);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result2);

        $params3 = [[$file5, $file4, $file3, $file2, $file1], 2];
        $result3 = $this->doMethod($controller, 'selectRecords', $params3);
        $this->assertEquals([$file5, $file4, $file3, $file2], $result3);

        $params4 = [[$file5, $file4, $file3, $file2, $file1], 3];
        $result4 = $this->doMethod($controller, 'selectRecords', $params4);
        $this->assertEquals([$file5, $file4, $file3], $result4);

        $params5 = [[$file5, $file4, $file3, $file2, $file1], 4];
        $result5 = $this->doMethod($controller, 'selectRecords', $params5);
        $this->assertEquals([$file5, $file4], $result5);

        $params6 = [[$file5, $file4, $file3, $file2, $file1], 5];
        $result6 = $this->doMethod($controller, 'selectRecords', $params6);
        $this->assertEquals([$file5], $result6);

        $params7 = [[$file5, $file4, $file3, $file2, $file1], 6];
        $result7 = $this->doMethod($controller, 'selectRecords', $params7);
        $this->assertEquals([], $result7);

        $params8 = [[], 0];
        $result8 = $this->doMethod($controller, 'selectRecords', $params8);
        $this->assertEquals([], $result8);
    }

    public function testConstructResponse() :void {
        $expected_data = [
            'count' => 5,
            'files' => [
                [
                    'id' => 1,
                    'time' => 1,
                    'name' => 'aaa.txt',
                    'path' => '/aaa.txt',
                    'modified_user' => 'userid',
                    'type' => 'file'
                ],
                [
                    'id' => 2,
                    'time' => 2,
                    'name' => 'bbb.txt',
                    'path' => '/share_with_a/bbb.txt',
                    'modified_user' => 'userA',
                    'type' => 'file'
                ],
                [
                    'id' => 3,
                    'time' => 3,
                    'name' => 'ccc.txt',
                    'path' => '/share_with_b/ccc.txt',
                    'modified_user' => 'userB',
                    'type' => 'file'
                ],
                [
                    'id' => 4,
                    'time' => 4,
                    'name' => 'ddd.txt',
                    'path' => '/share_with_a/test/ddd.txt',
                    'modified_user' => 'userA',
                    'type' => 'file'
                ],
                [
                    'id' => 5,
                    'time' => 5,
                    'name' => 'eee.txt',
                    'path' => '/share_with_c/eee.txt',
                    'modified_user' => 'userC',
                    'type' => 'file'
                ]
            ]
        ];

        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getMtime')->willReturn(1);
        $file1->method('getUploadTime')->willReturn(1);
        $file1->method('getName')->willReturn('aaa.txt');
        $file1->method('getPath')->willReturn('/userid/files/aaa.txt');
        $file1->method('getType')->willReturn('file');

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getMtime')->willReturn(2);
        $file2->method('getUploadTime')->willReturn(2);
        $file2->method('getName')->willReturn('bbb.txt');
        $file2->method('getPath')->willReturn('/userid/files/share_with_a/bbb.txt');
        $file2->method('getType')->willReturn('file');

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getMtime')->willReturn(3);
        $file3->method('getUploadTime')->willReturn(3);
        $file3->method('getName')->willReturn('ccc.txt');
        $file3->method('getPath')->willReturn('/userid/files/share_with_b/ccc.txt');
        $file3->method('getType')->willReturn('file');

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getMtime')->willReturn(4);
        $file4->method('getUploadTime')->willReturn(4);
        $file4->method('getName')->willReturn('ddd.txt');
        $file4->method('getPath')->willReturn('/userid/files/share_with_a/test/ddd.txt');
        $file4->method('getType')->willReturn('file');

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getMtime')->willReturn(5);
        $file5->method('getUploadTime')->willReturn(5);
        $file5->method('getName')->willReturn('eee.txt');
        $file5->method('getPath')->willReturn('/userid/files/share_with_c/eee.txt');
        $file5->method('getType')->willReturn('file');

        $entity1 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity1->method('getUser')->willReturn('userid');

        $entity2 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity2->method('getUser')->willReturn('userA');

        $entity3 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity3->method('getUser')->willReturn('userB');

        $entity4 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity4->method('getUser')->willReturn('userC');

        $map = [
            [1, 1, $entity1],
            [2, 2, $entity2],
            [3, 3, $entity3],
            [4, 4, $entity2],
            [5, 5, $entity4]
        ];
        $mapper = $this->getMockBuilder(FileUpdateMapper::class)->disableOriginalConstructor()->getMock();
        $mapper->method('find')->will($this->returnValueMap($map));

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $mapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $params = [[$file1, $file2, $file3, $file4, $file5], 'userid'];
        $result = $this->doMethod($controller, 'constructResponse', $params);
        $this->assertEquals($expected_data, $result);
    }

    public function testGetRecentArgumentPathIsNull() :void {
        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = 'query parameter since is missing';

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $response = $controller->getRecent(null);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }

    public function testGetRecentArgumentSinceIsInvalid() :void {
        $since = '/aaa';
        $since2 = '-1';
        $since3 = '-100';
        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = 'since is invalid number';

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->fileUpdateMapper,
            $this->cacheExtendedMapper,
            $this->logger
        );

        $response = $controller->getRecent($since);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());

        $response = $controller->getRecent($since2);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());

        $response = $controller->getRecent($since3);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }

    public function testGetRecentSuccess() :void {
        $since = '0';
        $expected_status = Http::STATUS_OK;
        $expected_data = [
            'count' => 5,
            'files' => [
                [
                    'id' => 5,
                    'time' => 5,
                    'name' => 'eee.txt',
                    'path' => '/share_with_c/eee.txt',
                    'modified_user' => 'userC',
                    'type' => 'file'
                ],
                [
                    'id' => 4,
                    'time' => 4,
                    'name' => 'ddd.txt',
                    'path' => '/share_with_a/test/ddd.txt',
                    'modified_user' => 'userA',
                    'type' => 'file'
                ],
                [
                    'id' => 3,
                    'time' => 3,
                    'name' => 'ccc.txt',
                    'path' => '/share_with_b/ccc.txt',
                    'modified_user' => 'userB',
                    'type' => 'file'
                ],
                [
                    'id' => 2,
                    'time' => 2,
                    'name' => 'bbb.txt',
                    'path' => '/share_with_a/bbb.txt',
                    'modified_user' => 'userA',
                    'type' => 'file'
                ],
                [
                    'id' => 1,
                    'time' => 1,
                    'name' => 'aaa.txt',
                    'path' => '/aaa.txt',
                    'modified_user' => 'userid',
                    'type' => 'file'
                ]
            ]
        ];

        $file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getMtime')->willReturn(1);
        $file1->method('getUploadTime')->willReturn(1);
        $file1->method('getName')->willReturn('aaa.txt');
        $file1->method('getPath')->willReturn('/userid/files/aaa.txt');
        $file1->method('getType')->willReturn('file');

        $file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getMtime')->willReturn(2);
        $file2->method('getUploadTime')->willReturn(2);
        $file2->method('getName')->willReturn('bbb.txt');
        $file2->method('getPath')->willReturn('/userid/files/share_with_a/bbb.txt');
        $file2->method('getType')->willReturn('file');

        $file3 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getMtime')->willReturn(3);
        $file3->method('getUploadTime')->willReturn(3);
        $file3->method('getName')->willReturn('ccc.txt');
        $file3->method('getPath')->willReturn('/userid/files/share_with_b/ccc.txt');
        $file3->method('getType')->willReturn('file');

        $file4 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getMtime')->willReturn(4);
        $file4->method('getUploadTime')->willReturn(4);
        $file4->method('getName')->willReturn('ddd.txt');
        $file4->method('getPath')->willReturn('/userid/files/share_with_a/test/ddd.txt');
        $file4->method('getType')->willReturn('file');

        $file5 = $this->getMockBuilder('OCP\Files\File')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getMtime')->willReturn(5);
        $file5->method('getUploadTime')->willReturn(5);
        $file5->method('getName')->willReturn('eee.txt');
        $file5->method('getPath')->willReturn('/userid/files/share_with_c/eee.txt');
        $file5->method('getType')->willReturn('file');

        $entity1 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity1->method('getUser')->willReturn('userid');

        $entity2 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity2->method('getUser')->willReturn('userA');

        $entity3 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity3->method('getUser')->willReturn('userB');

        $entity4 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity4->method('getUser')->willReturn('userC');

        $map1 = [
            [1, 1, $entity1],
            [2, 2, $entity2],
            [3, 3, $entity3],
            [4, 4, $entity2],
            [5, 5, $entity4]
        ];
        $mapper = $this->getMockBuilder(FileUpdateMapper::class)->disableOriginalConstructor()->getMock();
        $mapper->method('find')->will($this->returnValueMap($map1));

        $map2 = [
            [1, [$file1]],
            [2, [$file2]],
            [3, [$file3]],
            [4, [$file4]],
            [5, [$file5]]
        ];

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder
            ->method('getById')
            ->will($this->returnValueMap($map2));

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $cacheExtended1 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended1->method('getFileid')->willReturn(1);

        $cacheExtended2 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended2->method('getFileid')->willReturn(2);

        $cacheExtended3 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended3->method('getFileid')->willReturn(3);

        $cacheExtended4 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended4->method('getFileid')->willReturn(4);

        $cacheExtended5 = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtended')->setMethods(['getFileid'])->getMock();
        $cacheExtended5->method('getFileid')->willReturn(5);

        $cacheExtendedMapper = $this->getMockBuilder('OCA\FileUploadNotification\Db\FileCacheExtendedMapper')->disableOriginalConstructor()->getMock();
        $cacheExtendedMapper
            ->method('findAll')
            ->withConsecutive([500, 0], [500, 500], [500, 0])
            ->willReturnOnConsecutiveCalls(
                [$cacheExtended5, $cacheExtended4, $cacheExtended3, $cacheExtended2, $cacheExtended1],
                [],
                []
            );

        $controller = new RecentController('file_upload_notification',
            $this->request,
            $this->config,
            $rootFolder,
            $this->userSession,
            $mapper,
            $cacheExtendedMapper,
            $this->logger
        );

        $response = $controller->getRecent($since);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }
}