<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

final class SubgraphIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionValueCombination
     */
    protected $dimensionValueCombination;


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionValueCombination $dimensionValueCombination
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionValueCombination $dimensionValueCombination)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionValueCombination = $dimensionValueCombination;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionValueCombination
     */
    public function getDimensionValueCombination(): DimensionValueCombination
    {
        return $this->dimensionValueCombination;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        $identityComponents = $this->jsonSerialize();
        Arrays::sortKeysRecursively($identityComponents);

        return md5(json_encode($identityComponents));
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->getHash();
    }

    /**
     * @return array
     */
    function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionValueCombination' => $this->dimensionValueCombination
        ];
    }
}
