<?php
namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;

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
     * @var AuthorizationService
     */
    protected $nodeAuthorizationService;

    /**
     * @Flow\Inject
     * @var FusionHtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param array $additionalAttributes additional attributes in the form ['<attribute-name>' => '<attibute-value>', ...] to be rendered in the element wrapping
     */
    public function wrapContentObject(NodeInterface $node, string $content, string $fusionPath, array $additionalAttributes = []): string
    {
        if ($this->needsMetadata($node, false) === false) {
            return $content;
        }

        $attributes = $additionalAttributes;
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes['tabindex'] = 0;
        $attributes = $this->addCssClasses($attributes, $node, $this->collectEditingClassNames($node));

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', ['typeof']);
    }

    /**
     * @param array $additionalAttributes additional attributes in the form ['<attribute-name>' => '<attibute-value>', ...] to be rendered in the element wrapping
     */
    public function wrapCurrentDocumentMetadata(NodeInterface $node, string $content, string $fusionPath, array $additionalAttributes = []): string
    {
        if ($this->needsMetadata($node, true) === false) {
            return $content;
        }

        $attributes = $additionalAttributes;
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes = $this->addCssClasses($attributes, $node, []);

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', ['typeof']);
    }

    /**
     * Add required CSS classes to the attributes.
     */
    protected function addCssClasses(array $attributes, NodeInterface $node, array $initialClasses = []): array
    {
        $classNames = $initialClasses;
        // FIXME: The `dimensionsAreMatchingTargetDimensionValues` method should become part of the NodeInterface if it is used here .
        if ($node instanceof Node && !$node->dimensionsAreMatchingTargetDimensionValues()) {
            $classNames[] = 'neos-contentelement-shine-through';
        }

        if ($classNames !== []) {
            $attributes['class'] = implode(' ', $classNames);
        }

        return $attributes;
    }

    /**
     * Collects CSS class names used for styling editable elements in the Neos backend.
     */
    protected function collectEditingClassNames(NodeInterface $node): array
    {
        $classNames = [];

        if ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            // This is needed since the backend relies on this class (should not be necessary)
            $classNames[] = 'neos-contentcollection';
        } else {
            $classNames[] = 'neos-contentelement';
        }

        if ($node->isRemoved()) {
            $classNames[] = 'neos-contentelement-removed';
        }

        if ($node->isHidden()) {
            $classNames[] = 'neos-contentelement-hidden';
        }

        if ($this->isInlineEditable($node) === false) {
            $classNames[] = 'neos-not-inline-editable';
        }

        return $classNames;
    }

    /**
     * Determine if the Node or one of it's properties is inline editable.
            $parsedType = TypeHandling::parseType($dataType);
     */
    protected function isInlineEditable(NodeInterface $node): bool
    {
        $uiConfiguration = $node->getNodeType()->hasConfiguration('ui') ? $node->getNodeType()->getConfiguration('ui') : [];
        return (
            (isset($uiConfiguration['inlineEditable']) && $uiConfiguration['inlineEditable'] === true) ||
            $this->hasInlineEditableProperties($node)
        );
    }

    /**
     * Checks if the given Node has any properties configured as 'inlineEditable'
     */
    protected function hasInlineEditableProperties(NodeInterface $node): bool
    {
        return array_reduce(array_values($node->getNodeType()->getProperties()), static function ($hasInlineEditableProperties, $propertyConfiguration) {
            return ($hasInlineEditableProperties || (isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['ui']['inlineEditable'] === true));
        }, false);
    }

    protected function needsMetadata(NodeInterface $node, bool $renderCurrentDocumentMetadata): bool
    {
        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        return ($contentContext->isInBackend() === true && ($renderCurrentDocumentMetadata === true || $this->nodeAuthorizationService->isGrantedToEditNode($node) === true));
    }
}
