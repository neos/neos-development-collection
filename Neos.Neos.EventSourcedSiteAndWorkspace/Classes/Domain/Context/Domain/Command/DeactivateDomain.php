<?php
namespace Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Domain\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventInterface;
use Neos\Neos\EventSourcedSiteAndWorkspace\Domain\ValueObject\SchemeHostPort;

final class DeactivateDomain implements EventInterface
{
    /**
     * @var SchemeHostPort
     */
    private $schemeHostPort;

    /**
     * ActivateDomain constructor.
     * @param SchemeHostPort $schemeHostPort
     */
    public function __construct(SchemeHostPort $schemeHostPort)
    {
        $this->schemeHostPort = $schemeHostPort;
    }

    /**
     * @return SchemeHostPort
     */
    public function getSchemeHostPort(): SchemeHostPort
    {
        return $this->schemeHostPort;
    }
}
