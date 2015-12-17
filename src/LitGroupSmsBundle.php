<?php
/**
 * This file is part of the "LitGroupSmsBundle" package.
 *
 * (c) LitGroup <http://litgroup.ru/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LitGroup\SmsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * LitGroupSmsBundle.
 *
 * @author Roman Shamritskiy <roman@litgroup.ru>
 */
class LitGroupSmsBundle extends Bundle
{
    public function getContainerExtension()
    {
        if ($this->extension === null) {
            $this->extension = $this->createContainerExtension();
        }

        return $this->extension;
    }
}