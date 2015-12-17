<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Tests\LitGroup\SmsBundle\DependencyInjection;

use GuzzleHttp\Client as GuzzleHttpClient;
use LitGroup\Sms\Gateway\CascadeGateway;
use LitGroup\Sms\Gateway\MockSms\MockSmsGateway;
use LitGroup\Sms\Gateway\NullGateway;
use LitGroup\Sms\Gateway\Smsc\SmscGateway;
use LitGroup\Sms\Logger\MessageLogger;
use LitGroup\Sms\MessageService;
use LitGroup\SmsBundle\DataCollector\MessageDataCollector;
use LitGroup\SmsBundle\DependencyInjection\Configuration;
use LitGroup\SmsBundle\DependencyInjection\LitGroupSmsExtension;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class LitGroupSmsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LitGroupSmsExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;


    protected function setUp()
    {
        $this->extension = new LitGroupSmsExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.debug', true);
    }

    protected function tearDown()
    {
        $this->extension = null;
        $this->container = null;
    }

    public function testGetAlias()
    {
        $extension = new LitGroupSmsExtension();

        $this->assertSame('litgroup_sms', $extension->getAlias());
    }

    public function testGetConfiguration()
    {
        $this->assertInstanceOf(
            Configuration::class,
            $this->extension->getConfiguration([], $this->container)
        );
    }

    public function getMessageServiceDefinitionTests()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider getMessageServiceDefinitionTests
     */
    public function testMessageServiceDefinition($messageLoggingEnabled)
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                ['message_logging' => $messageLoggingEnabled]
            )
        ]);

        $this->assertParameter('litgroup_sms.message_service.class', MessageService::class);

        $alias = $this->container->getAlias('litgroup_sms');
        $this->assertSame('litgroup_sms.message_service', (string) $alias);

        $definition = $this->container->getDefinition('litgroup_sms.message_service');
        $this->assertSame('%litgroup_sms.message_service.class%', $definition->getClass());
        $this->assertCount(1, $definition->getArguments());
        $this->assertReference($definition->getArgument(0), 'litgroup_sms.gateway');

        $setLoggerCall = $definition->getMethodCalls()[0];
        $this->assertSame('setLogger', $setLoggerCall[0]);
        $this->assertReference($setLoggerCall[1][0], 'logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);

        $tag = $definition->getTag('monolog.logger');
        $this->assertCount(1, $tag);
        $this->assertSame('litgroup_sms', $tag[0]['channel']);

        if ($messageLoggingEnabled) {
            $setMessageLoggerCall = $definition->getMethodCalls()[1];
            $this->assertSame('setMessageLogger', $setMessageLoggerCall[0]);
            $this->assertReference($setMessageLoggerCall[1][0], 'litgroup_sms.message_logger');
        } else {
            $this->assertCount(1, $definition->getMethodCalls());
        }
    }

    public function testMessageLoggerAndDataCollectorDefinitionsIfEnabled()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                ['message_logging' => true]
            )
        ]);

        $this->assertParameter('litgroup_sms.message_logger.class', MessageLogger::class);
        $this->assertParameter('litgroup_sms.data_collector.class', MessageDataCollector::class);

        $loggerDefinition = $this->container->getDefinition('litgroup_sms.message_logger');
        $this->assertSame('%litgroup_sms.message_logger.class%', $loggerDefinition->getClass());
        $this->assertFalse($loggerDefinition->isPublic());

        $collectorDefinition = $this->container->getDefinition('litgroup_sms.data_collector');
        $this->assertSame('%litgroup_sms.data_collector.class%', $collectorDefinition->getClass());
        $this->assertFalse($collectorDefinition->isPublic());
        $this->assertReference($collectorDefinition->getArgument(0), 'litgroup_sms.message_logger');
        $this->assertTrue($collectorDefinition->hasTag('data_collector'));
    }

    public function testMessageLoggerAndDataCollectorDefinitionsIfDisabled()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                ['message_logging' => false]
            )
        ]);

        $this->assertFalse($this->container->hasParameter('litgroup_sms.message_logger.class'));
        $this->assertFalse($this->container->hasParameter('litgroup_sms.data_collector.class'));

        $this->assertFalse($this->container->hasDefinition('litgroup_sms.message_logger'));
        $this->assertFalse($this->container->hasDefinition('litgroup_sms.data_collector'));
    }

    public function testHttpClientDefinition()
    {
        $this->loadExtension([$this->getBaseConfig()]);

        $this->assertParameter('litgroup_sms.http_client.class', GuzzleHttpClient::class);

        $definition = $this->container->getDefinition('litgroup_sms.http_client');
        $this->assertSame('%litgroup_sms.http_client.class%', $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    public function testSingleGatewayByIdDefinition()
    {
        $this->loadExtension([
           array_merge(
               $this->getBaseConfig(),
               [
                   'gateways' => [
                       'my_gateway' => 'my_gateway_service'
                   ]
               ]
           )
        ]);

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway'),
            'litgroup_sms.gateway.my_gateway',
            false
        );

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway.my_gateway'),
            'my_gateway_service',
            false
        );
    }

    public function testSmscGateway()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                [
                    'gateways' => [
                        'default' => [
                            'type' => 'smsc',
                            'user' => 'User',
                            'password' => 'Password',
                            'connect_timeout' => 10.0,
                            'timeout' => 20.0
                        ]
                    ]
                ]
            )
        ]);

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway'),
            'litgroup_sms.gateway.default',
            false
        );

        $definition = $this->container->getDefinition('litgroup_sms.gateway.default');
        $this->assertSame(SmscGateway::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('User', $definition->getArgument(0));
        $this->assertSame('Password', $definition->getArgument(1));
        $this->assertReference($definition->getArgument(2), 'litgroup_sms.http_client');
        $this->assertEquals(10.0, $definition->getArgument(3));
        $this->assertEquals(20.0, $definition->getArgument(4));
    }

    public function testMockSmsGateway()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                [
                    'gateways' => [
                        'default' => [
                            'type' => 'mock_sms',
                            'host' => 'example.com',
                            'port' => 9931,
                            'connect_timeout' => 10.0,
                            'timeout' => 20.0
                        ]
                    ]
                ]
            )
        ]);

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway'),
            'litgroup_sms.gateway.default',
            false
        );

        $definition = $this->container->getDefinition('litgroup_sms.gateway.default');
        $this->assertSame(MockSmsGateway::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertReference($definition->getArgument(0), 'litgroup_sms.http_client');
        $this->assertSame('example.com', $definition->getArgument(1));
        $this->assertSame(9931, $definition->getArgument(2));
        $this->assertEquals(10.0, $definition->getArgument(3));
        $this->assertEquals(20.0, $definition->getArgument(4));
    }

    public function testCascadeGateway()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                [
                    'gateways' => [
                        'default' => 'my_gateway_service',
                        'reserve' => 'my_reserve_gateway_service'
                    ]
                ]
            )
        ]);

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway.default'),
            'my_gateway_service',
            false
        );

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway.reserve'),
            'my_reserve_gateway_service',
            false
        );

        $this->assertAlias(
            $this->container->getAlias('litgroup_sms.gateway'),
            'litgroup_sms.cascade_gateway',
            false
        );

        $definition = $this->container->getDefinition('litgroup_sms.cascade_gateway');
        $this->assertSame(CascadeGateway::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());

        $calls = $definition->getMethodCalls();

        $this->assertSame('setLogger', $calls[0][0]);
        $this->assertReference($calls[0][1][0], 'logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);

        $this->assertSame('addGateway', $calls[1][0]);
        $this->assertSame('default', $calls[1][1][0]);
        $this->assertReference($calls[1][1][1], 'litgroup_sms.gateway.default');

        $this->assertSame('addGateway', $calls[2][0]);
        $this->assertSame('reserve', $calls[2][1][0]);
        $this->assertReference($calls[2][1][1], 'litgroup_sms.gateway.reserve');

        $tag = $definition->getTag('monolog.logger');
        $this->assertCount(1, $tag);
        $this->assertSame('litgroup_sms', $tag[0]['channel']);
    }

    public function testDisableDelivery()
    {
        $this->loadExtension([
            array_merge(
                $this->getBaseConfig(),
                ['disable_delivery' => true]
            )
        ]);

        $definition = $this->container->getDefinition('litgroup_sms.gateway');
        $this->assertSame(NullGateway::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    /**
     * @return array
     */
    private function getBaseConfig()
    {
        return [
            'message_logging' => false,
            'disable_delivery' => false,
            'gateways' => [
                'default' => 'my_gateway'
            ]
        ];
    }

    /**
     * @param array $configs
     */
    private function loadExtension(array $configs)
    {
        $this->extension->load($configs, $this->container);
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function assertParameter($key, $value)
    {
        $this->assertTrue($this->container->hasParameter($key));
        $this->assertSame($value, $this->container->getParameter($key));
    }

    /**
     * @param Reference|mixed $reference
     * @param string          $target
     * @param integer         $invalidBehavior
     */
    private function assertReference(
        $reference,
        $target,
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
    ) {
        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame($invalidBehavior, $reference->getInvalidBehavior());
        $this->assertSame($target, (string) $reference);
    }

    /**
     * @param Alias|mixed $alias
     * @param string      $target
     * @param bool        $public
     */
    private function assertAlias($alias, $target, $public = true)
    {
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame($target, (string) $alias);
        $this->assertSame((boolean) $public, $alias->isPublic());
    }
}
