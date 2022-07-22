<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

#[Flow\Scope('singleton')]
final class NodeTypeManagerObjectFactory
{

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="fallbackNodeType")
     * @var string
     */
    protected $fallbackNodeTypeName;

    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ObjectManagerInterface $objectManager,
    )
    {
    }


    public function buildNodeTypeManager(): NodeTypeManager
    {
        return new NodeTypeManager(
            $this->configurationManager,
            $this->objectManager,
            $this->fallbackNodeTypeName
        );
    }
}
