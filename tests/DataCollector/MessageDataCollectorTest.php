<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Tests\LitGroup\SmsBundle\DataCollector;

use LitGroup\Sms\Logger\MessageLogger;
use LitGroup\Sms\Message;
use LitGroup\SmsBundle\DataCollector\MessageDataCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageLogger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var MessageDataCollector
     */
    private $collector;



    protected function setUp()
    {
        $this->logger = $this->getMock(MessageLogger::class);
        $this->collector = new MessageDataCollector($this->logger);
    }

    protected function tearDown()
    {
        $this->logger = null;
        $this->collector = null;
    }

    public function testWithEmptyLogger()
    {
        $this->logger
            ->expects($this->any())
            ->method('getMessages')
            ->willReturn([]);

        $this->collect();
        $this->assertSame(0, $this->collector->getMessageCount());
        $this->assertSame([], $this->collector->getMessages());
    }

    public function testWithMessages()
    {
        $messages = [
            new Message(),
            new Message(),
        ];

        $this->logger
            ->expects($this->any())
            ->method('getMessages')
            ->willReturn($messages);

        $this->collect();
        $this->assertSame(2, $this->collector->getMessageCount());
        $this->assertSame($messages, $this->collector->getMessages());
    }

    public function testGetName()
    {
        $this->assertSame('litgroup_sms', $this->collector->getName());
    }

    private function collect()
    {
        $this->collector->collect(
            $this->getMock(Request::class, [], [], '', false, false),
            $this->getMock(Response::class, [], [], '', false, false)
        );
    }

}
