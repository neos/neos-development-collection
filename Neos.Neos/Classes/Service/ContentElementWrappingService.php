<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;
use Neos\Neos\FrontendRouting\NodeAddressFactory;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService
{
    /**
     * @Flow\Inject
     * @var FusionHtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param array<string,string> $additionalAttributes
     */
    public function wrapContentObject(
        Node $node,
        string $content,
        string $fusionPath,
        array $additionalAttributes = []
    ): ?string {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->contentRepositoryId
        );

        // TODO: reenable permissions
        //if ($this->nodeAuthorizationService->isGrantedToEditNode($node) === false) {
        //    return $content;
        //}


        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($node);
        $attributes = $additionalAttributes;
        $attributes['data-__neos-fusion-path'] = $fusionPath;
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();

        // Define all attribute names as exclusive via the `exclusiveAttributes` parameter, to prevent the data of
        // two different nodes to be concatenated into the attributes of a single html node.
        // This way an outer div is added, if the wrapped content already has node related data-attributes set.
        return $this->htmlAugmenter->addAttributes(
            $content,
            $attributes,
            'div',
            array_keys($attributes)
        );
    }
}
