<?php
/**
 * This file is part of the "litgroup/sms" package.
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
                ->booleanNode('logging')
                    ->defaultValue($this->debug)
                ->end()
                ->booleanNode('disable_delivery')
                    ->defaultFalse()
                ->end()
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
                                ->values(['smsc', 'null_gateway'])
                            ->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}