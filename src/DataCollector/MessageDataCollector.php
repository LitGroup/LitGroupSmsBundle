<?php
/**
 * This file is part of the "litgroup/sms" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LitGroup\SmsBundle\DataCollector;

use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * @var ContainerInterface
     */
    private $container;


    /**
     * MessageDataCollector constructor.
     *
     * We don't inject the message logger and message service here
     * to avoid the creation of these objects when no emails are sent.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // TODO: Implement collect() method.
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'litgroup_sms';
    }

}