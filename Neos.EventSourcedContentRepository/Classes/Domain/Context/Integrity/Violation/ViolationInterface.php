<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An integrity violation (e.g. missing tethered node)
 */
interface ViolationInterface
{
    public function getParameters(): array;
}
