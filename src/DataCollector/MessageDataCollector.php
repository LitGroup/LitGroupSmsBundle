<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LitGroup\SmsBundle\DataCollector;

use LitGroup\Sms\Logger\MessageLogger;
use LitGroup\Sms\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * MessageDataCollector.
 *
 * @author Roman Shamritskiy <roman@litgroup.ru>
 */
class MessageDataCollector extends DataCollector
{
    /**
     * @var MessageLogger
     */
    private $logger;

    /**
     * MessageDataCollector constructor.
     *
     * @param MessageLogger $logger
     */
    public function __construct(MessageLogger $logger)
    {
        $this->logger = $logger;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = $this->logger->getMessages();
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->data;
    }

    /**
     * @return integer
     */
    public function getMessageCount()
    {
        return count($this->data);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'litgroup_sms';
    }
}