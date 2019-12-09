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


use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class MissingTetheredNode implements ViolationInterface
{
    /**
     * @var NodeName
     */
    private $nodeName;

    private function __construct(NodeName $nodeName)
    {
        $this->nodeName = $nodeName;
    }

    public static function fromNodeName(string $nodeName): self
    {
        return new static(NodeName::fromString($nodeName));
    }

    public function getDescription(): string
    {
        return sprintf('Tethered node "%s" is missing', $this->nodeName);
    }

    public function getParameters(): array
    {
        return ['nodeName' => $this->nodeName];
    }
}
