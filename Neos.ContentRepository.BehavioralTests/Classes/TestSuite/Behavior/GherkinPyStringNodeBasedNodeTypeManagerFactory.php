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

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Core\NodeType\ClosureNodeTypeProvider;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Factory for node type managers from gherkin py strings
 */
final class GherkinPyStringNodeBasedNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public static ?NodeTypeManager $nodeTypesToUse = null;

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        if (!self::$nodeTypesToUse) {
            throw new \DomainException('NodeTypeManagerFactory uninitialized');
        }
        return self::$nodeTypesToUse;
    }

    public static function initializeWithPyStringNode(PyStringNode $nodeTypesToUse): void
    {
        self::$nodeTypesToUse = new NodeTypeManager(
            new ClosureNodeTypeProvider(
                fn (): array => Yaml::parse($nodeTypesToUse->getRaw()) ?? [],
            )
        );
    }

    public static function reset(): void
    {
        self::$nodeTypesToUse = null;
    }
}
