<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Tests\LitGroup\SmsBundle;

use LitGroup\SmsBundle\DependencyInjection\LitGroupSmsExtension;
use LitGroup\SmsBundle\LitGroupSmsBundle;

class LitGroupSmsBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new LitGroupSmsBundle();

        $extension = $bundle->getContainerExtension();
        $this->assertInstanceOf(LitGroupSmsExtension::class, $extension);
        $this->assertSame($extension, $bundle->getContainerExtension());
    }
}
