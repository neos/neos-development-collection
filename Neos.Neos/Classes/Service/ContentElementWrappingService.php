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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Domain\Model\NodeCacheEntryIdentifier;
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
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

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

    public function __construct()
    {
    }

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param array<string,string> $additionalAttributes
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     */
    public function wrapContentObject(
        NodeInterface $node,
        string $content,
        string $fusionPath,
        array $additionalAttributes = []
    ): ?string {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->getSubgraphIdentity()->contentRepositoryIdentifier
        );

        if (
            $this->isContentStreamOfLiveWorkspace(
                $node->getSubgraphIdentity()->contentStreamIdentifier,
                $contentRepository
            )
        ) {
            return $content;
        }

        // TODO: reenable permissions
        //if ($this->nodeAuthorizationService->isGrantedToEditNode($node) === false) {
        //    return $content;
        //}


        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($node);
        $attributes = $additionalAttributes;
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();

        $this->renderedNodes[NodeCacheEntryIdentifier::fromNode($node)->getCacheEntryIdentifier()] = $node;

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
     * @param array<string,mixed> $additionalAttributes
     * additional attributes in the form ['<attribute-name>' => '<attibute-value>', ...]
     * to be rendered in the element wrapping
     */
    public function wrapCurrentDocumentMetadata(
        NodeInterface $node,
        string $content,
        string $fusionPath,
        array $additionalAttributes = [],
        ?NodeInterface $siteNode = null
    ): string {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->getSubgraphIdentity()->contentRepositoryIdentifier
        );
        if ($this->needsMetadata($node, $contentRepository, true) === false) {
            return $content;
        }

        $attributes = $additionalAttributes;
        $attributes['data-node-__typoscript-path'] = $fusionPath; // @deprecated
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes = $this->addGenericEditingMetadata($attributes, $node);
        $attributes = $this->addNodePropertyAttributes($attributes, $node);
        $attributes = $this->addDocumentMetadata($contentRepository, $attributes, $node, $siteNode);
        $attributes = $this->addCssClasses($attributes, $node, []);

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', ['typeof']);
    }

    /**
     * Collects metadata attributes used to allow editing of the node in the Neos backend.
     *
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    protected function addGenericEditingMetadata(array $attributes, NodeInterface $node): array
    {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->getSubgraphIdentity()->contentRepositoryIdentifier
        );
        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($node);
        $attributes['typeof'] = 'typo3:' . $node->getNodeType()->getName();
        $attributes['about'] = $nodeAddress->serializeForUri();
        $attributes['data-node-_identifier'] = (string)$node->getNodeAggregateIdentifier();
        $attributes['data-node-__workspace-name'] = $nodeAddress->workspaceName;
        $attributes['data-node-__label'] = $node->getLabel();

        if ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            $attributes['rel'] = 'typo3:content-collection';
        }

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getSubgraphIdentity()
        );
        $parentNode = $nodeAccessor->findParentNode($node);
        // these properties are needed together with the current NodeType to evaluate Node Type Constraints
        // TODO: this can probably be greatly cleaned up once we do not use CreateJS or VIE anymore.
        if ($parentNode) {
            $attributes['data-node-__parent-node-type'] = $parentNode->getNodeType()->getName();
        }

        if ($node->getClassification()->isTethered()) {
            $attributes['data-node-_name'] = $node->getNodeName();
            $attributes['data-node-_is-autocreated'] = 'true';
        }

        if ($parentNode && $parentNode->getClassification()->isTethered()) {
            $attributes['data-node-_parent-is-autocreated'] = 'true';
            // we shall only add these properties if the parent is actually auto-created;
            // as the Node-Type-Switcher in the UI relies on that.
            $attributes['data-node-__parent-node-name'] = $parentNode->getNodeName();
            $attributes['data-node-__grandparent-node-type']
                = $nodeAccessor->findParentNode($parentNode)?->getNodeType()->getName();
        }

        return $attributes;
    }

    /**
     * Adds node properties to the given $attributes collection and returns the extended array
     *
     * @param array<string,mixed> $attributes
     * @return array<string,mixed> the merged attributes
     */
    protected function addNodePropertyAttributes(array $attributes, NodeInterface $node): array
    {
        foreach (array_keys($node->getNodeType()->getProperties()) as $propertyName) {
            if ($propertyName[0] === '_' && $propertyName[1] === '_') {
                // skip fully-private properties
                continue;
            }
            $attributes = array_merge($attributes, $this->renderNodePropertyAttribute($node, $propertyName));
        }

        return $attributes;
    }

    /**
     * Renders data attributes needed for the given node property.
     *
     * @return array<string,mixed>
     */
    protected function renderNodePropertyAttribute(NodeInterface $node, string $propertyName): array
    {
        $attributes = [];

        // skip the node name of the site node - TODO: Why do we need this?
        if ($propertyName === '_name' && $node->getNodeType()->isOfType('Neos.Neos:Site')) {
            return $attributes;
        }

        $dataType = $node->getNodeType()->getPropertyType($propertyName);
        $dasherizedPropertyName = $this->dasherize($propertyName);

        $propertyValue = $node->getProperty($propertyName);
        $propertyValue = $propertyValue === null ? '' : $propertyValue;
        $propertyValue = !is_string($propertyValue) ? json_encode($propertyValue) : $propertyValue;

        if ($dataType !== 'string') {
            $attributes['data-nodedatatype-' . $dasherizedPropertyName] = 'xsd:' . $dataType;
        }

        $attributes['data-node-' . $dasherizedPropertyName] = $propertyValue;

        return $attributes;
    }

    /**
     * Collects metadata for the Neos backend specifically for document nodes.
     *
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    protected function addDocumentMetadata(
        ContentRepository $contentRepository,
        array $attributes,
        NodeInterface $node,
        ?NodeInterface $siteNode
    ): array {
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        $nodeAddress = $nodeAddressFactory->createFromNode($node);
        if (!$siteNode instanceof NodeInterface) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getSubgraphIdentity()
            );

            $siteCandidate = $node;
            while ($siteCandidate instanceof NodeInterface) {
                if ($siteCandidate->getNodeType()->isOfType('Neos.Neos:Site')) {
                    $siteNode = $siteCandidate;
                    break;
                }
                $siteCandidate = $nodeAccessor->findParentNode($siteCandidate);
            }
        }
        $siteNodeAddress = null;
        if ($siteNode instanceof NodeInterface) {
            $siteNodeAddress = $nodeAddressFactory->createFromNode($siteNode);
        }
        $attributes['data-neos-site-name'] = $siteNode?->getNodeName();
        $attributes['data-neos-site-node-context-path'] = $siteNodeAddress?->serializeForUri();
        // Add the workspace of the content repository context to the attributes
        $attributes['data-neos-context-workspace-name'] = $nodeAddress->workspaceName;
        $attributes['data-neos-context-dimensions'] = json_encode($nodeAddress->dimensionSpacePoint);

        if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
            $attributes['data-node-__read-only'] = 'true';
            $attributes['data-nodedatatype-__read-only'] = 'boolean';
        }

        return $attributes;
    }

    /**
     * Add required CSS classes to the attributes.
     *
     * @param array<string,mixed> $attributes
     * @param array<string,mixed> $initialClasses
     * @return array<string,mixed>
     */
    protected function addCssClasses(array $attributes, NodeInterface $node, array $initialClasses = []): array
    {
        $classNames = $initialClasses;
        if (!$node->getSubgraphIdentity()->dimensionSpacePoint->equals($node->getOriginDimensionSpacePoint())) {
            $classNames[] = 'neos-contentelement-shine-through';
        }

        if ($classNames !== []) {
            $attributes['class'] = implode(' ', $classNames);
        }

        return $attributes;
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
        $contentRepository = $this->contentRepositoryRegistry->get(
            $documentNode->getSubgraphIdentity()->contentRepositoryIdentifier
        );
        if (
            $this->isContentStreamOfLiveWorkspace(
                $documentNode->getSubgraphIdentity()->contentStreamIdentifier,
                $contentRepository
            )
        ) {
            return;
        }

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $documentNode->getSubgraphIdentity()
        );

        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);

        foreach ($nodeAccessor->findChildNodes($documentNode) as $node) {
            if ($node->getNodeType()->isOfType('Neos.Neos:Document') === true) {
                continue;
            }

            if (isset($this->renderedNodes[(string)$node->getNodeAggregateIdentifier()]) === false) {
                $serializedNode = json_encode($this->nodeInfoHelper->renderNode($node));
                $nodeContextPath = $nodeAddressFactory->createFromNode($node)->serializeForUri();
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
     * @throws \Neos\Eel\Exception
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     */
    public function getNonRenderedContentNodeMetadata(NodeInterface $documentNode): string
    {
        $this->userLocaleService->switchToUILocale();

        $this->appendNonRenderedContentNodeMetadata($documentNode);
        $nonRenderedContentNodeMetadata = $this->nonRenderedContentNodeMetadata;
        $this->clearNonRenderedContentNodeMetadata();
        $this->clearRenderedNodesArray();

        $this->userLocaleService->switchToUILocale(true);

        return $nonRenderedContentNodeMetadata;
    }

    /**
     * Converts camelCased strings to lower cased and non-camel-cased strings
     */
    protected function dasherize(string $value): string
    {
        return strtolower(trim(preg_replace('/[A-Z]/', '-$0', $value) ?: '', '-'));
    }

    protected function needsMetadata(
        NodeInterface $node,
        ContentRepository $contentRepository,
        bool $renderCurrentDocumentMetadata
    ): bool {
        return $this->isContentStreamOfLiveWorkspace(
            $node->getSubgraphIdentity()->contentStreamIdentifier,
            $contentRepository
        )
             && ($renderCurrentDocumentMetadata === true
                || $this->nodeAuthorizationService->isGrantedToEditNode($node) === true);
    }

    private function isContentStreamOfLiveWorkspace(
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentRepository $contentRepository
    ): bool {
        return $contentRepository->getWorkspaceFinder()
            ->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier)
            ?->getWorkspaceName()->isLive() ?: false;
    }
}
