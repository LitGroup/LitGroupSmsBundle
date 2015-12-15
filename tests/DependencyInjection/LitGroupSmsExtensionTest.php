<?php
/**
 * This file is part of the "litgroup/sms" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Tests\LitGroup\SmsBundle\DependencyInjection;

use LitGroup\SmsBundle\DependencyInjection\Configuration;
use LitGroup\SmsBundle\DependencyInjection\LitGroupSmsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LitGroupSmsExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAlias()
    {
        $extension = new LitGroupSmsExtension();

        $this->assertSame('litgroup_sms', $extension->getAlias());
    }

    public function testGetConfiguration()
    {
        $extension = new LitGroupSmsExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $this->assertInstanceOf(
            Configuration::class,
            $extension->getConfiguration([], $container)
        );
    }
}
