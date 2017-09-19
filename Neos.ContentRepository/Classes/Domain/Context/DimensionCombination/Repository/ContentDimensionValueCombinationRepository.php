<?php

namespace Neos\ContentRepository\Domain\DimensionCombination\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain;
use Neos\Flow\Annotations as Flow;

/**
 * The repository for content dimension value combinations
 *
 * @Flow\Scope("singleton")
 * @package Neos\ContentRepository
 */
class ContentDimensionValueCombinationRepository
{
    /**
     * @Flow\Inject
     * @var Domain\Service\ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @var array
     */
    protected $contentDimensionValueCombinations;


    /**
     * @return array|Domain\ValueObject\DimensionValueCombination[]
     */
    public function findAll(): array
    {
        if (is_null($this->contentDimensionValueCombinations)) {
            $this->contentDimensionValueCombinations = [];
            foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $rawDimensionCombination) {
                $this->contentDimensionValueCombinations[] = new Domain\ValueObject\DimensionValueCombination($rawDimensionCombination);
            }
        }

        return $this->contentDimensionValueCombinations;
    }
}
