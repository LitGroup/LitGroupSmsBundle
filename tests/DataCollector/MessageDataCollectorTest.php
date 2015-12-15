<?php
/**
 * This file is part of the "litgroup/sms" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Tests\LitGroup\SmsBundle\DataCollector;

use LitGroup\SmsBundle\DataCollector\MessageDataCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var MessageDataCollector
     */
    private $collector;


    protected function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);
        $this->collector = new MessageDataCollector($this->container);
    }

    protected function tearDown()
    {
        $this->container = null;
        $this->collector = null;
    }

    public function testGetName()
    {
        $this->assertSame('litgroup_sms', $this->collector->getName());
    }
}
