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

use Neos\Neos\Domain\ValueObject\SchemeHostPort;
use Neos\Neos\Exception;

final class DomainDoesNotExist extends Exception
{
    /**
     * DomainDoesNotExist constructor.
     * @param SchemeHostPort $schemeHostPort
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(SchemeHostPort $schemeHostPort, $code = 0, \Throwable $previous = null)
    {
        $message = 'The domain does not exists: ' . (string)$schemeHostPort;
        parent::__construct($message, $code, $previous);
    }

}
