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
use JsonException;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Factory for node type managers from gherkin py strings
 */
final class GherkinPyStringNodeBasedNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        if (!file_exists(self::cacheFileName($contentRepositoryId))) {
            throw new \DomainException(sprintf('NodeTypeManagerFactory uninitialized for ContentRepository "%s"', $contentRepositoryId->value));
        }
        $nodeTypesConfigurationJson = file_get_contents(self::cacheFileName($contentRepositoryId));
        try {
            $nodeTypesConfiguration = json_decode($nodeTypesConfigurationJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to parse JSON for node types configuration: %s', $nodeTypesConfigurationJson));
        }
        return new NodeTypeManager(
            fn () => $nodeTypesConfiguration,
            new class implements NodeLabelGeneratorFactoryInterface {
                public function create(NodeType $nodeType): NodeLabelGeneratorInterface
                {
                    return new class implements NodeLabelGeneratorInterface {
                        public function getLabel(Node $node): string
                        {
                            return $node->nodeTypeName->value;
                        }
                    };
                }
            }
        );
    }

    public static function registerNodeTypeConfigurationForContentRepository(ContentRepositoryId $contentRepositoryId, array $nodeTypeConfiguration): void
    {
        file_put_contents(self::cacheFileName($contentRepositoryId), json_encode($nodeTypeConfiguration, JSON_THROW_ON_ERROR));
    }

    public static function reset(): void
    {

    }

    private static function cacheFileName(ContentRepositoryId $contentRepositoryId): string
    {
        return '/tmp/nodeTypesConfiguration_' . $contentRepositoryId->value . '.json';
    }
}
