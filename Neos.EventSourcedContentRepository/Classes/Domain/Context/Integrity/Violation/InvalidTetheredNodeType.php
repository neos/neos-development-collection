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
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class InvalidTetheredNodeType implements ViolationInterface
{

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var NodeTypeName
     */
    private $expectedNodeTypeName;

    /**
     * @var NodeTypeName
     */
    private $actualNodeTypeName;

    public function __construct(NodeName $nodeName, NodeTypeName $expectedNodeTypeName, NodeTypeName $actualNodeTypeName)
    {
        $this->nodeName = $nodeName;
        $this->expectedNodeTypeName = $expectedNodeTypeName;
        $this->actualNodeTypeName = $actualNodeTypeName;
    }

    public static function fromNodeNameAndTypes(string $nodeName, string $expectedNodeType, string $actualNodeType): self
    {
        return new static(NodeName::fromString($nodeName), NodeTypeName::fromString($expectedNodeType), NodeTypeName::fromString($actualNodeType));
    }

    public static function getType(): string
    {
        return 'Invalid Tethered Node Type';
    }

    public function getDescription(): string
    {
        return sprintf('Tethered node "%s" has a wrong type. Expected: %s, actual: %s', $this->nodeName, $this->expectedNodeTypeName, $this->actualNodeTypeName);
    }

    /**
     * Note actualNodeTypeName is not part of the hash => Two Violations with the same name and expected node type are considered to be the same violation
     *
     * @return array
     */
    public function getParameters(): array
    {
        return [
            'nodeName' => $this->nodeName,
            'expectedNodeType' => $this->expectedNodeTypeName,
            'actualNodeType' => $this->actualNodeTypeName,
        ];
    }
}
