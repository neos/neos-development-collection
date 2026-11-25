<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\ReferenceLabel\ReferenceLabelGeneratorInterface;

/**
 * Eel helper for ContentRepository References
 */
class ReferenceHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ReferenceLabelGeneratorInterface $referenceLabelGenerator;

    /**
     * Renders the actual reference label in Fusion.
     */
    public function label(Reference $reference): string
    {
        return $this->referenceLabelGenerator->getLabel($reference);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
