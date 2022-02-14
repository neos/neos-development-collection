<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\EnableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifierCollection;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeVariantSelectionStrategyIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedNeosAdjustments\FusionCaching\ContentCacheFlusher;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\AbstractChange;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\ReloadContentOutOfBand;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;
use Neos\EventSourcedNeosAdjustments\Ui\Service\NodePropertyConversionService;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Neos\Ui\Domain\Model\RenderedNodeDomAddress;

/**
 * Changes a property on a node
 */
class Property extends AbstractChange
{
    /**
     * @Flow\Inject
     * @var NodePropertyConversionService
     */
    protected $nodePropertyConversionService;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * The node dom address
     *
     * @var RenderedNodeDomAddress
     */
    protected $nodeDomAddress;

    /**
     * The name of the property to be changed
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The value, the property will be set to
     *
     * @var string
     */
    protected $value;

    /**
     * The change has been initiated from the inline editing
     *
     * @var bool
     */
    protected $isInline;

    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * Set the property name
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Get the property name
     *
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Set the node dom address
     *
     * @param RenderedNodeDomAddress $nodeDomAddress
     * @return void
     */
    public function setNodeDomAddress(RenderedNodeDomAddress $nodeDomAddress = null)
    {
        $this->nodeDomAddress = $nodeDomAddress;
    }

    /**
     * Get the node dom address
     *
     * @return RenderedNodeDomAddress
     */
    public function getNodeDomAddress()
    {
        return $this->nodeDomAddress;
    }

    /**
     * Set the value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set isInline
     *
     * @param bool $isInline
     */
    public function setIsInline($isInline)
    {
        $this->isInline = $isInline;
    }

    /**
     * Get isInline
     *
     * @return bool
     */
    public function getIsInline()
    {
        return $this->isInline;
    }

    /**
     * Checks whether this change can be applied to the subject
     *
     * @return boolean
     */
    public function canApply(): bool
    {
        $nodeType = $this->getSubject()->getNodeType();
        $propertyName = $this->getPropertyName();
        $nodeTypeProperties = $nodeType->getProperties();

        return isset($nodeTypeProperties[$propertyName]);
    }

    /**
     * Applies this change
     *
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointNotFound
     */
    public function apply(): void
    {
        if ($this->canApply()) {
            $node = $this->getSubject();

            $propertyName = $this->getPropertyName();

            // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call, so we need to trigger another method beforehand.
            $node->getNodeType()->getFullConfiguration();
            $propertyType = $node->getNodeType()->getPropertyType($propertyName);
            $userIdentifier = $this->getInitiatingUserIdentifier();

            // Use extra commands for reference handling
            if ($propertyType === 'reference' || $propertyType === 'references') {
                $value = $this->getValue();
                $destinationNodeAggregateIdentifiers = [];
                if ($propertyType === 'reference') {
                    if (!empty($value)) {
                        $destinationNodeAggregateIdentifiers[] = NodeAggregateIdentifier::fromString($value);
                    }
                }

                if ($propertyType === 'references') {
                    /** @var array $values */
                    $values = $value;
                    if (is_array($values)) {
                        foreach ($values as $singleNodeAggregateIdentifier) {
                            $destinationNodeAggregateIdentifiers[] = NodeAggregateIdentifier::fromString($singleNodeAggregateIdentifier);
                        }
                    }
                }

                $commandResult = $this->nodeAggregateCommandHandler->handleSetNodeReferences(
                    new SetNodeReferences(
                        $node->getContentStreamIdentifier(),
                        $node->getNodeAggregateIdentifier(),
                        $node->getOriginDimensionSpacePoint(),
                        new NodeAggregateIdentifierCollection($destinationNodeAggregateIdentifiers),
                        PropertyName::fromString($propertyName),
                        $this->getInitiatingUserIdentifier()
                    )
                );
            } else {
                $value = $this->nodePropertyConversionService->convert(
                    $node->getNodeType(),
                    $propertyName,
                    $this->getValue()
                );

                // TODO: Make changing the node type a separated, specific/defined change operation.
                if ($propertyName[0] !== '_' || $propertyName === '_hiddenInIndex') {
                    $commandResult = $this->nodeAggregateCommandHandler->handleSetNodeProperties(
                        new SetNodeProperties(
                            $node->getContentStreamIdentifier(),
                            $node->getNodeAggregateIdentifier(),
                            $node->getOriginDimensionSpacePoint(),
                            PropertyValuesToWrite::fromArray(
                                [
                                    $propertyName => $value
                                ]
                            ),
                            $this->getInitiatingUserIdentifier()
                        )
                    );
                } else {
                    // property starts with "_"
                    if ($propertyName === '_nodeType') {
                        $commandResult = $this->nodeAggregateCommandHandler->handleChangeNodeAggregateType(
                            $command = new ChangeNodeAggregateType(
                                $node->getContentStreamIdentifier(),
                                $node->getNodeAggregateIdentifier(),
                                NodeTypeName::fromString($value),
                                NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::delete(),
                                $userIdentifier
                            )
                        );
                    } elseif ($propertyName === '_hidden') {
                        if ($value === true) {
                            $commandResult = $this->nodeAggregateCommandHandler->handleDisableNodeAggregate(
                                new DisableNodeAggregate(
                                    $node->getContentStreamIdentifier(),
                                    $node->getNodeAggregateIdentifier(),
                                    $node->getOriginDimensionSpacePoint(),
                                    NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_SPECIALIZATIONS,
                                    $userIdentifier
                                )
                            );
                        } else {
                            // unhide
                            $commandResult = $this->nodeAggregateCommandHandler->handleEnableNodeAggregate(
                                new EnableNodeAggregate(
                                    $node->getContentStreamIdentifier(),
                                    $node->getNodeAggregateIdentifier(),
                                    $node->getOriginDimensionSpacePoint(),
                                    NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_SPECIALIZATIONS,
                                    $userIdentifier
                                )
                            );
                        }
                    } else {
                        throw new \Exception("TODO FIX");
                    }
                }
            }

            if ($commandResult) {
                $commandResult->blockUntilProjectionsAreUpToDate();
            }

            // !!! REMEMBER: we are not allowed to use $node anymore, because it may have been modified by the commands above.
            // Thus, we need to re-fetch it (as a workaround; until we do not need this anymore)
            $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
            $node = $nodeAccessor->findByIdentifier($node->getNodeAggregateIdentifier());

            $this->updateWorkspaceInfo();

            $reloadIfChangedConfigurationPath = sprintf('properties.%s.ui.reloadIfChanged', $propertyName);
            if (!$this->getIsInline() && $node->getNodeType()->getConfiguration($reloadIfChangedConfigurationPath)) {
                if ($this->getNodeDomAddress() && $this->getNodeDomAddress()->getFusionPath() && $nodeAccessor->findParentNode($node)->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
                    // we render content directly as response of this operation, so we need to flush the caches
                    $this->contentCacheFlusher->flushNodeAggregate($node->getContentStreamIdentifier(), $node->getNodeAggregateIdentifier());
                    $reloadContentOutOfBand = new ReloadContentOutOfBand();
                    $reloadContentOutOfBand->setNode($node);
                    $reloadContentOutOfBand->setNodeDomAddress($this->getNodeDomAddress());
                    $this->feedbackCollection->add($reloadContentOutOfBand);
                } else {
                    // we render content directly as response of this operation, so we need to flush the caches
                    $this->contentCacheFlusher->flushNodeAggregate($node->getContentStreamIdentifier(), $node->getNodeAggregateIdentifier());
                    $this->reloadDocument($node);
                }
            }

            $reloadPageIfChangedConfigurationPath = sprintf('properties.%s.ui.reloadPageIfChanged', $propertyName);
            if (!$this->getIsInline() && $node->getNodeType()->getConfiguration($reloadPageIfChangedConfigurationPath)) {
                // we render content directly as response of this operation, so we need to flush the caches
                $this->contentCacheFlusher->flushNodeAggregate($node->getContentStreamIdentifier(), $node->getNodeAggregateIdentifier());
                $this->reloadDocument($node);
            }

            // This might be needed to update node label and other things that we can calculate only on the server
            $updateNodeInfo = new UpdateNodeInfo();
            $updateNodeInfo->setNode($node);
            $this->feedbackCollection->add($updateNodeInfo);
        }
    }
}
