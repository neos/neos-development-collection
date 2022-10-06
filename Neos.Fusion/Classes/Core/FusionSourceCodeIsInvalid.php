<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Exception;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class FusionSourceCodeIsInvalid extends Exception
{
    public static function becauseTheSourceCodeIsEmpty(?string $contextPathAndFileName): self
    {
        return new self("The sourcecode of the FusionSourceCode must not be empty. $contextPathAndFileName", 1657963664);
    }

    public static function becauseTheFileNameIsNotReadable(string $attemptedFileName): self
    {
        return new self("Trying to read Fusion source code from file, but '$attemptedFileName' is not readable.", 1657963790);
    }
}
