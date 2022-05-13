<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Content;

use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A search term for use in {@see ContentSubgraphInterface::findDescendants()} API.
 *
 * @Flow\Proxy(false)
 */
final class SearchTerm
{
    /**
     * @var string
     */
    private $term;

    private function __construct(string $term)
    {
        $this->term = $term;
    }
    /**
     * Create a new Fulltext search term (i.e. search across all properties)
     */
    public static function fulltext(string $term): self
    {
        return new self($term);
    }

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }
}
