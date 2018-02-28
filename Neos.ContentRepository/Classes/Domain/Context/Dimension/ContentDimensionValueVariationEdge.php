<?php
namespace Neos\ContentRepository\Domain\Context\Dimension;

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
 * The content dimension variation edge domain model
 */
final class ContentDimensionValueVariationEdge
{
    /**
     * @var ContentDimensionValue
     */
    protected $generalization;

    /**
     * @var ContentDimensionValue
     */
    protected $specialization;


    /**
     * @param ContentDimensionValue $specialization
     * @param ContentDimensionValue $generalization
     */
    public function __construct(ContentDimensionValue $specialization, ContentDimensionValue $generalization)
    {
        $this->specialization = $specialization;
        $this->generalization = $generalization;
    }

    /**
     * @return ContentDimensionValue
     */
    public function getSpecialization(): ContentDimensionValue
    {
        return $this->specialization;
    }

    /**
     * @return ContentDimensionValue
     */
    public function getGeneralization(): ContentDimensionValue
    {
        return $this->generalization;
    }
}
