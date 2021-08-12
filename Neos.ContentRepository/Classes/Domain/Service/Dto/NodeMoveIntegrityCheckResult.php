<?php

namespace Neos\ContentRepository\Domain\Service\Dto;
/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodeMoveIntegrityCheckResult
{
    /**
     * @var string
     */
    protected $nodeLabel;

    /**
     * @var NodeMoveIntegrityCheckResultPart[]
     */
    protected $parts;

    /**
     * @param NodeMoveIntegrityCheckResultPart[] $parts
     */
    private function __construct(string $nodeLabel, array $parts)
    {
        $this->nodeLabel = $nodeLabel;
        $this->parts = $parts;
    }

    public static function createForNode(\Neos\ContentRepository\Domain\Model\NodeInterface $nodeToMove): self
    {
        return new self($nodeToMove->getLabel(), []);
    }

    public function withResultParts(array $parts): self
    {
        return new self($this->nodeLabel, $parts);
    }

    public function hasIntegrityViolations(): bool
    {
        throw new \RuntimeException('TODO');
    }

    public function getPlainMessage(): string
    {
        $message = 'Node ' . $this->nodeLabel . ' can not be moved.' . chr(10) .
            'When moving Document Nodes, they are moved across all dimensions.' . chr(10) .
            'For node ' . $this->nodeLabel . ', we attempted to move it across the following dimensions:' . chr(10);

        foreach ($this->parts as $part) {
            assert ($part instanceof NodeMoveIntegrityCheckResultPart);
            $message .= ' - ' . $part->getDimensionsLabel();
            if ($part->isViolated()) {
                $message .= ' (ERROR: Non-Existing Parent)';
            }
            $message .= chr(10);
        }

        $message .= 'To fix this, you can .... TODO';

    }

}
