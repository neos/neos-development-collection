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

namespace Neos\Neos\FrontendRouting\Projection;

/**
 * @implements \IteratorAggregate<DocumentNodeInfo>
 */
final readonly class DocumentNodeInfos implements \IteratorAggregate
{
    /**
     * @param array<DocumentNodeInfo> $documentNodeInfos
     */
    private function __construct(
        private array $documentNodeInfos
    ) {
    }

    /**
     * @param array<DocumentNodeInfo> $documentNodeInfos
     * @return static
     */
    public static function create(array $documentNodeInfos): static
    {
        return new static($documentNodeInfos);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->documentNodeInfos;
    }
}
