<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;

/**
 * @api to access the Reference's label in PHP, in Fusion one can use ${Neos.Reference.label(reference)}.
 */
interface ReferenceLabelGeneratorInterface
{
    /**
     * Render a reference label
     * @api
     */
    public function getLabel(Reference $reference): string;
}
