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

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;

/**
 * Fake factory for testing. Note that the factory MUST be initialised BEFORE the content repository is fetched.
 * Any changes after initialing a cr are lost UNLESS the content repository is rebuild.
 */
final class FakeNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    private static ?NodeTypeManager $nodeTypeManager = null;

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        if (self::$nodeTypeManager) {
            return self::$nodeTypeManager;
        }
        if (isset($options['nodeTypes'])) {
            // allows to be configured for testing
            return NodeTypeManager::createFromArrayConfiguration($options['nodeTypes']);
        }
        throw new \RuntimeException('NodeTypeManagerFactory uninitialized');
    }

    /**
     * @param array<string,mixed> $nodeTypesToUse
     */
    public static function setConfiguration(array $nodeTypesToUse): void
    {
        self::$nodeTypeManager = NodeTypeManager::createFromArrayConfiguration(
            $nodeTypesToUse
        );
    }

    public static function setNodeTypeManager(NodeTypeManager $nodeTypeManager): void
    {
        self::$nodeTypeManager = $nodeTypeManager;
    }

    public static function reset(): void
    {
        self::$nodeTypeManager = null;
    }
}
