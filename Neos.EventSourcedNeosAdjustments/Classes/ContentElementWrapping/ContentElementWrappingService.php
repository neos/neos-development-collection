<?php
namespace Neos\EventSourcedNeosAdjustments\ContentElementWrapping;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Ui\Domain\Service\UserLocaleService;
use Neos\Neos\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Service\Mapping\NodePropertyConverterService;
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
     * @Flow\Inject
     * @var NodePropertyConverterService
     */
    protected $nodePropertyConverterService;


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
     * All editable nodes rendered in the document
     *
     * @var array
     */
    protected $renderedNodes = [];


    /**
     * String containing `<script>` tags for non rendered nodes
     *
     * @var string
     */
    protected $nonRenderedContentNodeMetadata;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param TraversableNodeInterface $node
     * @param string $content
     * @param string $fusionPath
     * @return string
     * @throws \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\Exception\NodeAddressCannotBeSerializedException
     */
    public function wrapContentObject(TraversableNodeInterface $node, $content, $fusionPath): string
    {
        $subgraph = $node->getSubgraph();
        if ($this->isContentStreamOfLiveWorkspace($subgraph->getContentStreamIdentifier())) {
            return $content;
        }


        // TODO: reenable permissions
        //if ($this->nodeAuthorizationService->isGrantedToEditNode($node) === false) {
        //    return $content;
        //}

        $attributes = [
            'data-__neos-node-contextpath' => $this->nodeAddressFactory->createFromNode($node)->serializeForUri(),
            'data-__neos-fusion-path' => $fusionPath
        ];

        $this->renderedNodes[(string)$node->getNodeIdentifier()] = $node;

        $this->userLocaleService->switchToUILocale();

        $serializedNode = json_encode($this->nodeInfoHelper->renderNode($node, $subgraph));

        $this->userLocaleService->switchToUILocale(true);

        $wrappedContent = $this->htmlAugmenter->addAttributes($content, $attributes, 'div');
        $nodeContextPath = $this->nodeAddressFactory->createFromNode($node)->serializeForUri();
        $wrappedContent .= "<script data-neos-nodedata>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeContextPath}'] = {$serializedNode}})()</script>";

        return $wrappedContent;
    }

    /**
     * Concatenate strings containing `<script>` tags for all child nodes not rendered
     * within the current document node. This way we can show e.g. content collections
     * within the structure tree which are not actually rendered.
     *
     * @param TraversableNodeInterface $documentNode
     * @return mixed
     * @throws \Neos\Eel\Exception
     * @throws \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\Exception\NodeAddressCannotBeSerializedException
     */
    protected function appendNonRenderedContentNodeMetadata(TraversableNodeInterface $documentNode)
    {
        if ($this->isContentStreamOfLiveWorkspace($documentNode->getSubgraph()->getContentStreamIdentifier())) {
            return '';
        }


        foreach ($documentNode->findChildNodes() as $node) {
            if ($node->getNodeType()->isOfType('Neos.Neos:Document') === true) {
                continue;
            }

            if (isset($this->renderedNodes[(string)$node->getNodeIdentifier()]) === false) {
                $serializedNode = json_encode($this->nodeInfoHelper->renderNode($node, $subgraph));
                $nodeContextPath = $this->nodeAddressFactory->createFromNode($node)->serializeForUri();
                $this->nonRenderedContentNodeMetadata .= "<script>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeContextPath}'] = {$serializedNode}})()</script>";
            }

            if ($subgraph->countChildNodes($node->getNodeIdentifier()) > 0) {
                $this->nonRenderedContentNodeMetadata .= $this->appendNonRenderedContentNodeMetadata($node);
            }
        }
    }

    /**
     * Clear rendered nodes helper array to prevent possible side effects.
     */
    protected function clearRenderedNodesArray()
    {
        $this->renderedNodes = [];
    }

    /**
     * Clear non rendered content node metadata to prevent possible side effects.
     */
    protected function clearNonRenderedContentNodeMetadata()
    {
        $this->nonRenderedContentNodeMetadata = '';
    }

    /**
     * @param TraversableNodeInterface $documentNode
     * @return string
     * @throws \Neos\Eel\Exception
     * @throws \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\Exception\NodeAddressCannotBeSerializedException
     */
    public function getNonRenderedContentNodeMetadata(TraversableNodeInterface $documentNode)
    {
        $this->userLocaleService->switchToUILocale();

        $this->appendNonRenderedContentNodeMetadata($documentNode);
        $nonRenderedContentNodeMetadata = $this->nonRenderedContentNodeMetadata;
        $this->clearNonRenderedContentNodeMetadata();
        $this->clearRenderedNodesArray();

        $this->userLocaleService->switchToUILocale(true);

        return $nonRenderedContentNodeMetadata;
    }

    private function isContentStreamOfLiveWorkspace(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier)->getWorkspaceName()->isLive();
    }
}

