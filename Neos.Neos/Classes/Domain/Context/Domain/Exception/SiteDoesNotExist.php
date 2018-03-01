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

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\Neos\Exception;

final class SiteDoesNotExist extends Exception
{
    /**
     * SiteDoesNotExist constructor.
     * @param NodeName $siteNodeName
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(NodeName $siteNodeName, $code = 0, \Throwable $previous = null)
    {
        $message = 'The site does not exists: ' . $siteNodeName;
        parent::__construct($message, $code, $previous);
    }

}
