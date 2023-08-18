<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Symfony\Component\Yaml\Yaml;

/**
 * Factory for node type managers from gherkin py strings
 */
final class GherkinPyStringNodeBasedNodeTypeManagerFactory
{
    public static function create(PyStringNode $serializedNodeTypesConfiguration, ?string $fallbackNodeTypeName = null): NodeTypeManager
    {
        return new NodeTypeManager(
            fn (): array => Yaml::parse($serializedNodeTypesConfiguration->getRaw()),
            new class implements NodeLabelGeneratorFactoryInterface {
                public function create(NodeType $nodeType): NodeLabelGeneratorInterface
                {
                    return new class implements NodeLabelGeneratorInterface {
                        public function getLabel(Node $node): string
                        {
                            return $node->nodeType->getLabel();
                        }
                    };
                }
            },
            $fallbackNodeTypeName
        );
    }
}
