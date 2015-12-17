<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LitGroup\SmsBundle\DependencyInjection;

use LitGroup\Sms\Gateway\MockSms\MockSmsGateway;
use LitGroup\Sms\Gateway\NullGateway;
use LitGroup\Sms\Gateway\Smsc\SmscGateway;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * LitGroupSmsBundle extension for service container.
 *
 * @author Roman Shamritskiy <roman@litgroup.ru>
 */
class LitGroupSmsExtension extends Extension
{
    private static $gatewayTypesDefinitions = [
        'smsc' => 'createSmscGatewayDefinition',
        'mock_sms' => 'createMockSmsGatewayDefinition'
    ];

    /**
     * Loads the extension configuration.
     *
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $this->loadMessageService($loader, $config, $container);
        $this->loadGuzzleHttpClient($loader);
        $this->loadGateway($loader, $config, $container);
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return 'litgroup_sms';
    }

    /**
     * @param Loader\XmlFileLoader $loader
     * @param array                $config
     * @param ContainerBuilder     $container
     */
    private function loadGateway(Loader\XmlFileLoader $loader, array $config, ContainerBuilder $container)
    {
        if ($config['disable_delivery'] === true) {
            $container->register('litgroup_sms.gateway', NullGateway::class)->setPublic(false);

            return;
        }

        $gatewayIds = [];
        foreach ($config['gateways'] as $gatewayName => $gatewayParams) {
            $gatewayIds[$gatewayName] = $this->getServiceIdByGatewayName($gatewayName);
            $this->addGatewayServiceDefinition($gatewayName, $gatewayIds[$gatewayName], $gatewayParams, $container);
        }

        if (count($gatewayIds) === 1) {
            $container->setAlias('litgroup_sms.gateway', new Alias(array_shift($gatewayIds), false));

            return;
        }

        $loader->load('cascading.xml');
        $cascadeDefinition = $container->getDefinition('litgroup_sms.cascade_gateway');

        foreach ($gatewayIds as $name => $serviceId) {
            $cascadeDefinition->addMethodCall('addGateway', [$name, new Reference($serviceId)]);
        }

        $container->setAlias('litgroup_sms.gateway', new Alias('litgroup_sms.cascade_gateway', false));
    }

    /**
     * @param Loader\XmlFileLoader $loader
     *
     * @return void
     */
    private function loadGuzzleHttpClient(Loader\XmlFileLoader $loader)
    {
        $loader->load('guzzle_http.xml');
    }

    /**
     * @param Loader\XmlFileLoader $loader
     * @param array                $config
     * @param ContainerBuilder     $container
     *
     * @return void
     */
    private function loadMessageService(Loader\XmlFileLoader $loader, array $config, ContainerBuilder $container)
    {
        $loader->load('message_service.xml');

        if ($config['message_logging'] === true) {
            $loader->load('message_logging.xml');

            $container
                ->getDefinition('litgroup_sms.message_service')
                    ->addMethodCall('setMessageLogger', [
                        new Reference('litgroup_sms.message_logger')
                    ])
            ;
        }
    }

    /**
     * @param string           $name
     * @param string           $serviceId
     * @param array            $params
     * @param ContainerBuilder $container
     *
     * @return string Id of a gateway service or alias.
     *
     * @throws InvalidConfigurationException
     */
    private function addGatewayServiceDefinition($name, $serviceId, array $params, ContainerBuilder $container)
    {
        if (isset($params['id']) && !empty($params['id'])) {
            $container->setAlias($serviceId, new Alias($params['id'], false));

            return;
        }

        if (!isset($params['type']) || empty($params['type'])) {
            throw new InvalidConfigurationException(
                sprintf('Type of the gateway "%s" should be set', $name)
            );
        }

        if (!array_key_exists($params['type'], self::$gatewayTypesDefinitions)) {
            throw new InvalidConfigurationException(
                sprintf('Gateway of type "%s" cannot be found.', $params['type'])
            );
        }

        $definition = call_user_func([$this, self::$gatewayTypesDefinitions[$params['type']]], $params);
        $container->setDefinition($serviceId, $definition);
    }

    /**
     * @param array $params
     *
     * @return Definition
     */
    private function createSmscGatewayDefinition(array $params)
    {
        $definition = new Definition();
        $definition->setClass(SmscGateway::class);
        $definition->setPublic(false);
        $definition->setArguments(
            [
                $params['user'],
                $params['password'],
                new Reference('litgroup_sms.http_client'),
                $params['connect_timeout'],
                $params['timeout']
            ]
        );

        return $definition;
    }

    /**
     * @param array $params
     *
     * @return Definition
     */
    private function createMockSmsGatewayDefinition(array $params)
    {
        $definition = new Definition();
        $definition->setClass(MockSmsGateway::class);
        $definition->setPublic(false);
        $definition->setArguments(
            [
                new Reference('litgroup_sms.http_client'),
                $params['host'],
                !empty($params['port']) ? $params['port'] : MockSmsGateway::DEFAULT_PORT,
                $params['connect_timeout'],
                $params['timeout']
            ]
        );

        return $definition;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getServiceIdByGatewayName($name)
    {
        return sprintf('litgroup_sms.gateway.%s', $name);
    }
}