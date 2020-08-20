<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Integrity;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violation\DisallowedTetheredNode;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violation\InvalidTetheredNodeType;
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violation\MissingTetheredNode;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class IntegrityViolationDetector
{

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * Find tethered nodes that...
     * * ...should not exist according to the node type schema
     * * ...are missing
     * * ...have an invalid type
     *
     * @param NodeType $nodeType
     * @return Violations
     */
    public function detectTetheredNodeViolations(NodeType $nodeType): Violations
    {
        $expectedTetheredNodes = array_map(static function (NodeType $nodeType) {
            return $nodeType->getName();
        }, $nodeType->getAutoCreatedChildNodes());
        $violations = Violations::create();

        foreach ($this->nodesOfType(NodeTypeName::fromString($nodeType->getName())) as $contentStreamIdentifier => $nodeAggregate) {
            $tetheredNodes = $this->contentGraph->findTetheredChildNodeAggregates($contentStreamIdentifier, $nodeAggregate->getIdentifier());
            $tetheredNodeTypes = [];
            foreach ($tetheredNodes as $tetheredNodeAggregate) {
                $tetheredNodeTypes[(string)$tetheredNodeAggregate->getNodeName()] = (string)$tetheredNodeAggregate->getNodeTypeName();
            }
            foreach ($expectedTetheredNodes as $nodeName => $expectedNodeType) {
                if (!array_key_exists($nodeName, $tetheredNodeTypes)) {
                    $violations = $violations->with(MissingTetheredNode::fromNodeName($nodeName));
                    continue;
                }
                if ($tetheredNodeTypes[$nodeName] !== $expectedNodeType) {
                    $violations = $violations->with(InvalidTetheredNodeType::fromNodeNameAndTypes($nodeName, $expectedNodeType, $tetheredNodeTypes[$nodeName]));
                }
            }
            foreach ($tetheredNodeTypes as $nodeName => $tetheredNodeType) {
                if (!array_key_exists($nodeName, $expectedTetheredNodes)) {
                    $violations = $violations->with(DisallowedTetheredNode::fromNodeName($nodeName));
                    continue;
                }
            }
        }

        return $violations;
    }


    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]|\Iterator
     */
    private function nodesOfType(NodeTypeName $nodeTypeName): \Iterator
    {
        $contentStreamIdentifiers = $this->contentGraph->findProjectedContentStreamIdentifiers();
        foreach ($contentStreamIdentifiers as $contentStreamIdentifier) {
            $nodeAggregates = $this->contentGraph->findNodeAggregatesByType($contentStreamIdentifier, $nodeTypeName);
            foreach ($nodeAggregates as $nodeAggregate) {
                yield $contentStreamIdentifier => $nodeAggregate;
            }
        }
    }
}
