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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * LitGroupSmsBundle semantic configuration.
 *
 * @author Roman Shamritskiy <roman@litgroup.ru>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var boolean
     */
    private $debug;

    /**
     * Configuration constructor.
     *
     * @param boolean $debug
     */
    public function __construct($debug)
    {
        $this->debug = (boolean) $debug;
    }

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('litgroup_sms');

        $rootNode
            ->fixXmlConfig('gateway')
            ->children()
                ->arrayNode('gateways')
                    ->isRequired()
                    ->performNoDeepMerging()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) { return ['id' => $v ]; })
                        ->end()
                        ->children()
                            ->scalarNode('id')->end()
                            ->enumNode('type')
                                ->values(['smsc', 'mock_sms'])
                            ->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->floatNode('connect_timeout')
                                ->defaultValue(0.0)
                                ->treatNullLike(0.0)
                            ->end()
                            ->floatNode('timeout')
                                ->defaultValue(0.0)
                                ->treatNullLike(0.0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('message_logging')
                    ->defaultValue($this->debug)
                    ->info('By default enabled in debug mode.')
                ->end()
                    ->booleanNode('disable_delivery')
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}