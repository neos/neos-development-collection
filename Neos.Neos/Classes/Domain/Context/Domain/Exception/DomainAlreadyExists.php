<?php

namespace Neos\Neos\Domain\Context\Domain\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Neos\Exception;

final class DomainAlreadyExists extends Exception
{
    public function __construct($hostname = "", $code = 0, \Throwable $previous = null)
    {
        $message = 'The domain already exists: ' . $hostname;
        parent::__construct($message, $code, $previous);
    }

}
