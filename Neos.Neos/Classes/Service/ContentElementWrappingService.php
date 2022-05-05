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


use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Ui\Domain\Service\UserLocaleService;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;
use Neos\Neos\Ui\Fusion\Helper\NodeInfoHelper;

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
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var UserLocaleService
     */
    protected $userLocaleService;

    /**
     * @Flow\Inject
     * @var NodeInfoHelper
     */
    protected $nodeInfoHelper;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * All editable nodes rendered in the document
     *
     * @var array<string,NodeInterface>
     */
    protected array $renderedNodes = [];

    /**
     * String containing `<script>` tags for non rendered nodes
     *
     * @var string
     */
    protected $nonRenderedContentNodeMetadata;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param string $content
     * @param string $fusionPath
     * @param array<string,string> $additionalAttributes
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     */
    public function wrapContentObject(
        NodeInterface $node,
                      $content,
                      $fusionPath,
        array $additionalAttributes = []
    ): ?string {
        if ($this->isContentStreamOfLiveWorkspace($node->getContentStreamIdentifier())) {
            return $content;
        }

        // TODO: reenable permissions
        //if ($this->nodeAuthorizationService->isGrantedToEditNode($node) === false) {
        //    return $content;
        //}

        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        $attributes = $additionalAttributes;
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();

        $this->renderedNodes[$node->getCacheEntryIdentifier()] = $node;

        $this->userLocaleService->switchToUILocale();

        $serializedNode = json_encode($this->nodeInfoHelper->renderNode($node));

        $this->userLocaleService->switchToUILocale(true);

        $wrappedContent = $this->htmlAugmenter->addAttributes($content, $attributes, 'div');
        $nodeContextPath = $nodeAddress->serializeForUri();
        /** @codingStandardsIgnoreStart */
        $wrappedContent .= "<script data-neos-nodedata>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeContextPath}'] = {$serializedNode}})()</script>";
        /** @codingStandardsIgnoreEnd */

        return $wrappedContent;
    }

    /**
     * Concatenate strings containing `<script>` tags for all child nodes not rendered
     * within the current document node. This way we can show e.g. content collections
     * within the structure tree which are not actually rendered.
     *
     * @throws \Neos\Eel\Exception
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     */
    protected function appendNonRenderedContentNodeMetadata(NodeInterface $documentNode): void
    {
        if ($this->isContentStreamOfLiveWorkspace($documentNode->getContentStreamIdentifier())) {
            return;
        }

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $documentNode->getContentStreamIdentifier(),
            $documentNode->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        foreach ($nodeAccessor->findChildNodes($documentNode) as $node) {
            if ($node->getNodeType()->isOfType('Neos.Neos:Document') === true) {
                continue;
            }

            if (isset($this->renderedNodes[(string)$node->getNodeAggregateIdentifier()]) === false) {
                $serializedNode = json_encode($this->nodeInfoHelper->renderNode($node));
                $nodeContextPath = $this->nodeAddressFactory->createFromNode($node)->serializeForUri();
                /** @codingStandardsIgnoreStart */
                $this->nonRenderedContentNodeMetadata .= "<script>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeContextPath}'] = {$serializedNode}})()</script>";
                /** @codingStandardsIgnoreEnd */
            }

            $nestedNodes = $nodeAccessor->findChildNodes($node);
            $hasChildNodes = false;
            foreach ($nestedNodes as $nestedNode) {
                $hasChildNodes = true;
                break;
            }

            if ($hasChildNodes) {
                $this->appendNonRenderedContentNodeMetadata($node);
            }
        }
    }

    /**
     * Clear rendered nodes helper array to prevent possible side effects.
     */
    protected function clearRenderedNodesArray(): void
    {
        $this->renderedNodes = [];
    }

    /**
     * Clear non rendered content node metadata to prevent possible side effects.
     */
    protected function clearNonRenderedContentNodeMetadata(): void
    {
        $this->nonRenderedContentNodeMetadata = '';
    }

    /**
     * @param NodeInterface $documentNode
     * @return string
     * @throws \Neos\Eel\Exception
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     */
    public function getNonRenderedContentNodeMetadata(NodeInterface $documentNode)
    {
        $this->userLocaleService->switchToUILocale();

        $this->appendNonRenderedContentNodeMetadata($documentNode);
        $nonRenderedContentNodeMetadata = $this->nonRenderedContentNodeMetadata;
        $this->clearNonRenderedContentNodeMetadata();
        $this->clearRenderedNodesArray();

        $this->userLocaleService->switchToUILocale(true);

        return $nonRenderedContentNodeMetadata;
    }

    private function isContentStreamOfLiveWorkspace(ContentStreamIdentifier $contentStreamIdentifier): bool
    {
        return $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier)
            ?->getWorkspaceName()->isLive() ?: false;
    }
}
