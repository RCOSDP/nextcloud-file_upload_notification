<?php

declare(strict_types=1);

namespace OCA\FileUpdateNotifications\Tests\Controller;

use OC\Files\Node\Root;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

use OCA\FileUpdateNotifications\Controller\RecentController;
use OCA\FileUpdateNotifications\Db\FileUpdateMapper;
use OCA\FileUpdateNotifications\Db\FileUpdate;

class RecentControllerTest extends \Test\TestCase {

    private $request;
    private $config;
    private $userFolder;
    private $rootFolder;
    private $user;
    private $userSession;
    private $mapper;
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

        $this->mapper = $this->getMockBuilder(FileUpdateMapper::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder(ILogger::class)->getMock();
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

    public function testIncludeRecordsBeforeTime() {
        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getMtime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getMtime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getMtime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getMtime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getMtime')->willReturn(5);

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

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
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
        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getMtime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getMtime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getMtime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getMtime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getMtime')->willReturn(5);

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $userFolder1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder1
            ->method('getRecent')
            ->withConsecutive([5, 0], [5, 5])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3, $file2, $file1],
                []
            );
        $params1 = [$userFolder1, 5, 0];
        $result1 = $this->doMethod($controller, 'getRecentRecords', $params1);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result1);

        $userFolder2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder2
            ->method('getRecent')
            ->withConsecutive([5, 0], [5, 5])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3, $file2, $file1],
                []
            );
        $params2 = [$userFolder2, 5, 1];
        $result2 = $this->doMethod($controller, 'getRecentRecords', $params2);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result2);

        $userFolder3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder3
            ->method('getRecent')
            ->with(5, 0)
            ->willReturn([$file5, $file4, $file3, $file2, $file1]);
        $params3 = [$userFolder3, 5, 2];
        $result3 = $this->doMethod($controller, 'getRecentRecords', $params3);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result3);

        $userFolder4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder4
            ->method('getRecent')
            ->with(5, 0)
            ->willReturn([$file5, $file4, $file3, $file2, $file1]);
        $params4 = [$userFolder4, 5, 3];
        $result4 = $this->doMethod($controller, 'getRecentRecords', $params4);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result4);

        $userFolder5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder5
            ->method('getRecent')
            ->with(5, 0)
            ->willReturn([$file5, $file4, $file3, $file2, $file1]);
        $params5 = [$userFolder5, 5, 4];
        $result5 = $this->doMethod($controller, 'getRecentRecords', $params5);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result5);

        $userFolder6 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder6
            ->method('getRecent')
            ->with(5, 0)
            ->willReturn([$file5, $file4, $file3, $file2, $file1]);
        $params6 = [$userFolder6, 5, 5];
        $result6 = $this->doMethod($controller, 'getRecentRecords', $params6);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result6);

        $userFolder7 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder7
            ->method('getRecent')
            ->withConsecutive([3, 0], [3, 3])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3],
                [$file2, $file1]
            );
        $params7 = [$userFolder7, 3, 0];
        $result7 = $this->doMethod($controller, 'getRecentRecords', $params7);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result7);

        $userFolder8 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder8
            ->method('getRecent')
            ->withConsecutive([3, 0], [3, 3])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3],
                [$file2, $file1]
            );
        $params8 = [$userFolder8, 3, 1];
        $result8 = $this->doMethod($controller, 'getRecentRecords', $params8);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result8);

        $userFolder9 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder9
            ->method('getRecent')
            ->withConsecutive([3, 0], [3, 3])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3],
                [$file2, $file1]
            );
        $params9 = [$userFolder9, 3, 2];
        $result9 = $this->doMethod($controller, 'getRecentRecords', $params9);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result9);

        $userFolder10 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder10
            ->method('getRecent')
            ->withConsecutive([3, 0], [3, 3])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4, $file3],
                [$file2, $file1]
            );
        $params10 = [$userFolder10, 3, 3];
        $result10= $this->doMethod($controller, 'getRecentRecords', $params10);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result10);

        $userFolder11 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder11
            ->method('getRecent')
            ->with(3, 0)
            ->willReturn([$file5, $file4, $file3]);
        $params11 = [$userFolder11, 3, 4];
        $result11 = $this->doMethod($controller, 'getRecentRecords', $params11);
        $this->assertEquals([$file5, $file4, $file3], $result11);



        $userFolder12 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder12
            ->method('getRecent')
            ->withConsecutive([2, 0], [2, 2], [2, 4])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4],
                [$file3, $file2],
                [$file1]
            );
        $params12 = [$userFolder12, 2, 0];
        $result12 = $this->doMethod($controller, 'getRecentRecords', $params12);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result12);

        $userFolder13 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder13
            ->method('getRecent')
            ->withConsecutive([2, 0], [2, 2], [2, 4])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4],
                [$file3, $file2],
                [$file1]
            );
        $params13 = [$userFolder13, 2, 1];
        $result13 = $this->doMethod($controller, 'getRecentRecords', $params13);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result13);

        $userFolder14 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder14
            ->method('getRecent')
            ->withConsecutive([2, 0], [2, 2], [2, 4])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4],
                [$file3, $file2],
                [$file1]
            );
        $params14 = [$userFolder14, 2, 2];
        $result14 = $this->doMethod($controller, 'getRecentRecords', $params14);
        $this->assertEquals([$file5, $file4, $file3, $file2, $file1], $result14);

        $userFolder15 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder15
            ->method('getRecent')
            ->withConsecutive([2, 0], [2, 2])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4],
                [$file3, $file2]
            );
        $params15 = [$userFolder15, 2, 3];
        $result15 = $this->doMethod($controller, 'getRecentRecords', $params15);
        $this->assertEquals([$file5, $file4, $file3, $file2], $result15);

        $userFolder16 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder16
            ->method('getRecent')
            ->withConsecutive([2, 0], [2, 2])
            ->willReturnOnConsecutiveCalls(
                [$file5, $file4],
                [$file3, $file2]
            );
        $params16 = [$userFolder16, 2, 4];
        $result16 = $this->doMethod($controller, 'getRecentRecords', $params16);
        $this->assertEquals([$file5, $file4, $file3, $file2], $result16);

        $userFolder17 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder17
            ->method('getRecent')
            ->with(2, 0)
            ->willReturn([$file5, $file4]);
        $params17 = [$userFolder17, 2, 5];
        $result17 = $this->doMethod($controller, 'getRecentRecords', $params17);
        $this->assertEquals([$file5, $file4], $result17);

        $userFolder18 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder18
            ->method('getRecent')
            ->withConsecutive([1, 0], [1, 1])
            ->willReturnOnConsecutiveCalls(
                [$file5],
                [$file4]
            );
        $params18 = [$userFolder18, 1, 5];
        $result18 = $this->doMethod($controller, 'getRecentRecords', $params18);
        $this->assertEquals([$file5, $file4], $result18);

        $userFolder19 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder19
            ->method('getRecent')
            ->withConsecutive([1, 0], [1, 1], [1, 2])
            ->willReturnOnConsecutiveCalls(
                [$file5],
                [$file4],
                [$file3]
            );
        $params19 = [$userFolder19, 1, 4];
        $result19 = $this->doMethod($controller, 'getRecentRecords', $params19);
        $this->assertEquals([$file5, $file4, $file3], $result19);
    }

    public function testGetUniqueRecords() :void {
        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getMtime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getMtime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getMtime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getMtime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getMtime')->willReturn(5);

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
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

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $argArray = [];
        $params = [$argArray];

        $result = $this->doMethod($controller, 'getUniqueRecords', $params);
        $this->assertEquals($expected_data, $result);
    }

    public function testSelectRecords() :void {
        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getMtime')->willReturn(1);

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getMtime')->willReturn(2);

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getMtime')->willReturn(3);

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getMtime')->willReturn(4);

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getMtime')->willReturn(5);

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
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
                    'mtime' => 1,
                    'name' => 'aaa.txt',
                    'path' => '/aaa.txt',
                    'modified_user' => 'userid'
                ],
                [
                    'id' => 2,
                    'mtime' => 2,
                    'name' => 'bbb.txt',
                    'path' => '/share_with_a/bbb.txt',
                    'modified_user' => 'userA'
                ],
                [
                    'id' => 3,
                    'mtime' => 3,
                    'name' => 'ccc.txt',
                    'path' => '/share_with_b/ccc.txt',
                    'modified_user' => 'userB'
                ],
                [
                    'id' => 4,
                    'mtime' => 4,
                    'name' => 'ddd.txt',
                    'path' => '/share_with_a/test/ddd.txt',
                    'modified_user' => 'userA'
                ],
                [
                    'id' => 5,
                    'mtime' => 5,
                    'name' => 'eee.txt',
                    'path' => '/share_with_c/eee.txt',
                    'modified_user' => 'userC'
                ]
            ]
        ];

        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getMtime')->willReturn(1);
        $file1->method('getName')->willReturn('aaa.txt');
        $file1->method('getPath')->willReturn('/userid/files/aaa.txt');

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getMtime')->willReturn(2);
        $file2->method('getName')->willReturn('bbb.txt');
        $file2->method('getPath')->willReturn('/userid/files/share_with_a/bbb.txt');

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getMtime')->willReturn(3);
        $file3->method('getName')->willReturn('ccc.txt');
        $file3->method('getPath')->willReturn('/userid/files/share_with_b/ccc.txt');

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getMtime')->willReturn(4);
        $file4->method('getName')->willReturn('ddd.txt');
        $file4->method('getPath')->willReturn('/userid/files/share_with_a/test/ddd.txt');

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getMtime')->willReturn(5);
        $file5->method('getName')->willReturn('eee.txt');
        $file5->method('getPath')->willReturn('/userid/files/share_with_c/eee.txt');

        $entity1 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity1->method('getUser')->willReturn('userid');

        $entity2 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity2->method('getUser')->willReturn('userA');

        $entity3 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity3->method('getUser')->willReturn('userB');

        $entity4 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
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

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $mapper,
            $this->logger
        );

        $params = [[$file1, $file2, $file3, $file4, $file5], 'userid'];
        $result = $this->doMethod($controller, 'constructResponse', $params);
        $this->assertEquals($expected_data, $result);
    }

    public function testGetRecentArgumentPathIsNull() :void {
        $expected_status = Http::STATUS_BAD_REQUEST;
        $expected_data = 'query parameter since is missing';

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
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

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $this->rootFolder,
            $this->userSession,
            $this->mapper,
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
                    'mtime' => 5,
                    'name' => 'eee.txt',
                    'path' => '/share_with_c/eee.txt',
                    'modified_user' => 'userC'
                ],
                [
                    'id' => 4,
                    'mtime' => 4,
                    'name' => 'ddd.txt',
                    'path' => '/share_with_a/test/ddd.txt',
                    'modified_user' => 'userA'
                ],
                [
                    'id' => 3,
                    'mtime' => 3,
                    'name' => 'ccc.txt',
                    'path' => '/share_with_b/ccc.txt',
                    'modified_user' => 'userB'
                ],
                [
                    'id' => 2,
                    'mtime' => 2,
                    'name' => 'bbb.txt',
                    'path' => '/share_with_a/bbb.txt',
                    'modified_user' => 'userA'
                ],
                [
                    'id' => 1,
                    'mtime' => 1,
                    'name' => 'aaa.txt',
                    'path' => '/aaa.txt',
                    'modified_user' => 'userid'
                ]
            ]
        ];

        $file1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file1->method('getId')->willReturn(1);
        $file1->method('getMtime')->willReturn(1);
        $file1->method('getName')->willReturn('aaa.txt');
        $file1->method('getPath')->willReturn('/userid/files/aaa.txt');

        $file2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file2->method('getId')->willReturn(2);
        $file2->method('getMtime')->willReturn(2);
        $file2->method('getName')->willReturn('bbb.txt');
        $file2->method('getPath')->willReturn('/userid/files/share_with_a/bbb.txt');

        $file3 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file3->method('getId')->willReturn(3);
        $file3->method('getMtime')->willReturn(3);
        $file3->method('getName')->willReturn('ccc.txt');
        $file3->method('getPath')->willReturn('/userid/files/share_with_b/ccc.txt');

        $file4 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file4->method('getId')->willReturn(4);
        $file4->method('getMtime')->willReturn(4);
        $file4->method('getName')->willReturn('ddd.txt');
        $file4->method('getPath')->willReturn('/userid/files/share_with_a/test/ddd.txt');

        $file5 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $file5->method('getId')->willReturn(5);
        $file5->method('getMtime')->willReturn(5);
        $file5->method('getName')->willReturn('eee.txt');
        $file5->method('getPath')->willReturn('/userid/files/share_with_c/eee.txt');

        $entity1 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity1->method('getUser')->willReturn('userid');

        $entity2 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity2->method('getUser')->willReturn('userA');

        $entity3 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
            ->setMethods(['getUser'])
            ->getMock();
        $entity3->method('getUser')->willReturn('userB');

        $entity4 = $this->getMockBuilder('OCA\FileUpdateNotifications\Db\FileUpdate')
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

        $records = [$file5, $file4, $file3, $file2, $file1];
        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder
            ->method('getRecent')
            ->withConsecutive([1000, 0], [100, 0])
            ->willReturnOnConsecutiveCalls(
                $records,
                $records
            );

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $controller = new RecentController('file-update-notifications',
            $this->request,
            $this->config,
            $rootFolder,
            $this->userSession,
            $mapper,
            $this->logger
        );

        $response = $controller->getRecent($since);
        $this->assertEquals($expected_status, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }
}