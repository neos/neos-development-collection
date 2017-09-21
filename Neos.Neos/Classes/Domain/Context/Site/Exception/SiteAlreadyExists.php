<?php
namespace Neos\Neos\Domain\Context\Site\Exception;

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

final class SiteAlreadyExists extends Exception
{
    public function __construct($siteNodeName = "", $code = 0, \Throwable $previous = null)
    {
        $message = 'The site already exists: ' . $siteNodeName;
        parent::__construct($message, $code, $previous);
    }

}
