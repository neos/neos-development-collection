<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\TypeConverter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Neos\FrontendRouting\NodeAddressFactory;

/**
 * To be removed legacy fragment for property mapping nodes in controllers.
 * MUST not be used and MUST be removed before Neos 9 release.
 * See issue: https://github.com/neos/neos-development-collection/issues/4873
 *
 * @Flow\Scope("singleton")
 * @deprecated must be removed before Neos 9 release!!!
 */
class NodeToNodeAddressStringConverter extends AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @var array<int,string>
     */
    protected $sourceTypes = [Node::class];

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @param Node $source
     * @param string $targetType
     * @param array<string,mixed> $subProperties
     * @return mixed|\Neos\Error\Messages\Error|string|null
     */
    public function convertFrom(
        $source,
        $targetType = null,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $source->contentRepositoryId
        );
        return NodeAddressFactory::create($contentRepository)->createFromNode($source)->serializeForUri();
    }
}
