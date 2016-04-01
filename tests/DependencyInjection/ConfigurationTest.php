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

use LitGroup\SmsBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private static $minimalConfig = [
        'gateways' => [
            'default' => [
                'id' => 'app.sms_gateway'
            ]
        ]
    ];

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testNoConfigForGateway()
    {
        $configuration = new Configuration(false);
        $processor = new Processor();

        $processor->processConfiguration($configuration, []);
    }

    public function getDefaultConfigsTests()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider getDefaultConfigsTests
     */
    public function testDefaultsConfigs($debug)
    {
        $configuration = new Configuration($debug);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [self::$minimalConfig]);
        $this->assertFalse($processedConfig['disable_delivery']);
        $this->assertSame($debug, $processedConfig['message_logging']);
    }

    public function getLoggingConfigTests()
    {
        return [
            [true, true],
            [null, true],
            [false, false],
        ];
    }

    /**
     * @dataProvider getLoggingConfigTests
     */
    public function testLoggingConfig($confValue, $expected)
    {
        $config = array_merge(
            ['message_logging' => $confValue],
            self::$minimalConfig
        );

        $configuration = new Configuration(false);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertSame($expected, $processedConfig['message_logging']);
    }

    public function getDisableDeliveryConfigTests()
    {
        return [
            [true, true],
            [null, true],
            [false, false],
        ];
    }

    /**
     * @dataProvider getDisableDeliveryConfigTests
     */
    public function testDisableDeliveryConf($confValue, $expected)
    {
        $config = array_merge(
            ['disable_delivery' => $confValue],
            self::$minimalConfig
        );

        $configuration = new Configuration(false);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertSame($expected, $processedConfig['disable_delivery']);
    }

    public function testGatewayProvidedByService()
    {
        $config = [
            'gateways' => [
                'gw1' => [
                    'id' => 'app.sms_gateway_1'
                ],
                'gw2' => 'app.sms_gateway_2'
            ]
        ];

        $configuration = new Configuration(false);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertCount(2, $processedConfig['gateways']);
        $this->assertSame('app.sms_gateway_1', $processedConfig['gateways']['gw1']['id']);
        $this->assertSame('app.sms_gateway_2', $processedConfig['gateways']['gw2']['id']);
    }

    public function getGatewayParamsTests()
    {
        return [
            /* SMSc */
            [
                [
                    'type' => 'smsc',
                    'user' => 'smscuser',
                    'password' => 'topsecret',
                    'connect_timeout' => 10.0,
                    'timeout' => 20.0
                ]
            ],
            [
                [
                    'type' => 'mock_sms',
                    'host' => 'example.com',
                    'port' => 9999,
                    'connect_timeout' => 10.0,
                    'timeout' => 20.0
                ]
            ]
        ];
    }

    /**
     * @dataProvider getGatewayParamsTests
     */
    public function testGatewayParams($params)
    {
        $config = [
            'gateways' => [
                'default' => $params,
            ]
        ];

        $configuration = new Configuration(false);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertCount(1, $processedConfig['gateways']);
        $this->assertCount(count($params), $processedConfig['gateways']['default']);
        $this->assertEquals($params, $processedConfig['gateways']['default']);
    }

    public function testGatewaysNoDeepMerge()
    {
        $configs =[
            [
                'gateways' => [
                    'gw1' => [
                        'type' => 'smsc',
                        'user' => 'User1',
                        'password' => 'Password1'
                    ]
                ]
            ],
            [
                'gateways' => [
                    'gw2' => [
                        'type' => 'mock_sms',
                        'host' => 'example.com',
                        'port' => 6666,
                    ]
                ]
            ]
        ];

        $configuration = new Configuration(false);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, $configs);
        $this->assertCount(1, $processedConfig['gateways']);
        $this->assertArrayHasKey('gw2', $processedConfig['gateways']);

        $this->assertSame('mock_sms', $processedConfig['gateways']['gw2']['type']);
        $this->assertSame('example.com', $processedConfig['gateways']['gw2']['host']);
        $this->assertSame(6666, $processedConfig['gateways']['gw2']['port']);
    }
}
