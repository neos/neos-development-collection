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
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Session\SessionInterface;
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
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var FusionHtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

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

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div');
    }

    /**
     * Add required CSS classes to the attributes.
     *
     * @param array<string,mixed> $attributes
     * @param array<string,mixed> $initialClasses
     * @return array<string,mixed>
     */
    protected function addCssClasses(array $attributes, Node $node, array $initialClasses = []): array
    {
        $classNames = $initialClasses;
        if (!$node->dimensionSpacePoint->equals($node->originDimensionSpacePoint)) {
            $classNames[] = 'neos-contentelement-shine-through';
        }

        if ($classNames !== []) {
            $attributes['class'] = implode(' ', $classNames);
        }

        return $attributes;
    }
}
