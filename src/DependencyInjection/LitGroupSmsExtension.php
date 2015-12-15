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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * LitGroupSmsBundle extension for service container.
 *
 * @author Roman Shamritskiy <roman@litgroup.ru>
 */
class LitGroupSmsExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $config, ContainerBuilder $container)
    {
        // TODO: Implement load() method.
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
}