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
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Feature\NodeTypeChange\Command\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
/** @codingStandardsIgnoreEnd */
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\EventSourcedNeosAdjustments\FusionCaching\ContentCacheFlusher;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\AbstractChange;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\ReloadContentOutOfBand;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;
use Neos\EventSourcedNeosAdjustments\Ui\Service\NodePropertyConversionService;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
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
     * The node dom address
     */
    protected ?RenderedNodeDomAddress $nodeDomAddress = null;

    /**
     * The name of the property to be changed
     */
    protected ?string $propertyName = null;

    /**
     * The value, the property will be set to
     * @var string|array<int|string,mixed>|null
     */
    protected string|array|null $value = null;

    /**
     * The change has been initiated from the inline editing
     */
    protected bool $isInline = false;

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

    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function setNodeDomAddress(RenderedNodeDomAddress $nodeDomAddress = null): void
    {
        $this->nodeDomAddress = $nodeDomAddress;
    }

    public function getNodeDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->nodeDomAddress;
    }

    /**
     * @param string|array<int|string,mixed>|null $value
     */
    public function setValue(string|array|null $value): void
    {
        $this->value = $value;
    }

    /**
     * @return string|array<int|string,mixed>|null
     */
    public function getValue(): string|array|null
    {
        return $this->value;
    }

    public function setIsInline(bool $isInline): void
    {
        $this->isInline = $isInline;
    }

    public function getIsInline(): bool
    {
        return $this->isInline;
    }

    /**
     * Checks whether this change can be applied to the subject
     */
    public function canApply(): bool
    {
        if (is_null($this->subject)) {
            return false;
        }
        $nodeType = $this->subject->getNodeType();
        $propertyName = $this->getPropertyName();
        $nodeTypeProperties = $nodeType->getProperties();

        return isset($nodeTypeProperties[$propertyName]);
    }

    /**
     * Applies this change
     *
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointNotFound
     */
    public function apply(): void
    {
        $subject = $this->subject;
        $propertyName = $this->getPropertyName();
        if ($this->canApply() && !is_null($subject) && !is_null($propertyName)) {
            // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call,
            // so we need to trigger another method beforehand.
            $subject->getNodeType()->getFullConfiguration();
            $propertyType = $subject->getNodeType()->getPropertyType($propertyName);
            $userIdentifier = $this->getInitiatingUserIdentifier();

            // Use extra commands for reference handling
            if ($propertyType === 'reference' || $propertyType === 'references') {
                $value = $this->getValue();
                $destinationNodeAggregateIdentifiers = [];
                if ($propertyType === 'reference') {
                    if (is_string($value) && !empty($value)) {
                        $destinationNodeAggregateIdentifiers[] = $value;
                    }
                }

                if ($propertyType === 'references') {
                    /** @var array<int,string> $values */
                    $values = $value;
                    if (is_array($values)) {
                        foreach ($values as $singleNodeAggregateIdentifier) {
                            $destinationNodeAggregateIdentifiers[] = $singleNodeAggregateIdentifier;
                        }
                    }
                }

                $commandResult = $this->nodeAggregateCommandHandler->handleSetNodeReferences(
                    new SetNodeReferences(
                        $subject->getContentStreamIdentifier(),
                        $subject->getNodeAggregateIdentifier(),
                        $subject->getOriginDimensionSpacePoint(),
                        NodeAggregateIdentifiers::fromArray($destinationNodeAggregateIdentifiers),
                        PropertyName::fromString($propertyName),
                        $this->getInitiatingUserIdentifier()
                    )
                );
            } else {
                $value = $this->nodePropertyConversionService->convert(
                    $subject->getNodeType(),
                    $propertyName,
                    $this->getValue()
                );

                // TODO: Make changing the node type a separated, specific/defined change operation.
                if ($propertyName[0] !== '_' || $propertyName === '_hiddenInIndex') {
                    $commandResult = $this->nodeAggregateCommandHandler->handleSetNodeProperties(
                        new SetNodeProperties(
                            $subject->getContentStreamIdentifier(),
                            $subject->getNodeAggregateIdentifier(),
                            $subject->getOriginDimensionSpacePoint(),
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
                                $subject->getContentStreamIdentifier(),
                                $subject->getNodeAggregateIdentifier(),
                                NodeTypeName::fromString($value),
                                NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE,
                                $userIdentifier
                            )
                        );
                    } elseif ($propertyName === '_hidden') {
                        if ($value === true) {
                            $commandResult = $this->nodeAggregateCommandHandler->handleDisableNodeAggregate(
                                new DisableNodeAggregate(
                                    $subject->getContentStreamIdentifier(),
                                    $subject->getNodeAggregateIdentifier(),
                                    $subject->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                                    NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
                                    $userIdentifier
                                )
                            );
                        } else {
                            // unhide
                            $commandResult = $this->nodeAggregateCommandHandler->handleEnableNodeAggregate(
                                new EnableNodeAggregate(
                                    $subject->getContentStreamIdentifier(),
                                    $subject->getNodeAggregateIdentifier(),
                                    $subject->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                                    NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
                                    $userIdentifier
                                )
                            );
                        }
                    } else {
                        throw new \Exception("TODO FIX");
                    }
                }
            }

            $commandResult->blockUntilProjectionsAreUpToDate();

            // !!! REMEMBER: we are not allowed to use $node anymore,
            // because it may have been modified by the commands above.
            // Thus, we need to re-fetch it (as a workaround; until we do not need this anymore)
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $subject->getContentStreamIdentifier(),
                $subject->getDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );
            $originalNodeAggregateIdentifier = $subject->getNodeAggregateIdentifier();
            $node = $nodeAccessor->findByIdentifier($subject->getNodeAggregateIdentifier());
            if (is_null($node)) {
                throw new \InvalidArgumentException(
                    'Cannot apply Property on missing node ' . $originalNodeAggregateIdentifier,
                    1645560836
                );
            }

            $this->updateWorkspaceInfo();
            $parentNode = $nodeAccessor->findParentNode($node);

            $reloadIfChangedConfigurationPath = sprintf('properties.%s.ui.reloadIfChanged', $propertyName);
            if (!$this->getIsInline() && $node->getNodeType()->getConfiguration($reloadIfChangedConfigurationPath)) {
                if ($this->getNodeDomAddress() && $this->getNodeDomAddress()->getFusionPath()
                    && $parentNode
                    && $parentNode->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
                    // we render content directly as response of this operation, so we need to flush the caches
                    $this->contentCacheFlusher->flushNodeAggregate(
                        $node->getContentStreamIdentifier(),
                        $node->getNodeAggregateIdentifier()
                    );
                    $reloadContentOutOfBand = new ReloadContentOutOfBand();
                    $reloadContentOutOfBand->setNode($node);
                    $reloadContentOutOfBand->setNodeDomAddress($this->getNodeDomAddress());
                    $this->feedbackCollection->add($reloadContentOutOfBand);
                } else {
                    // we render content directly as response of this operation, so we need to flush the caches
                    $this->contentCacheFlusher->flushNodeAggregate(
                        $node->getContentStreamIdentifier(),
                        $node->getNodeAggregateIdentifier()
                    );
                    $this->reloadDocument($node);
                }
            }

            $reloadPageIfChangedConfigurationPath = sprintf('properties.%s.ui.reloadPageIfChanged', $propertyName);
            if (!$this->getIsInline()
                && $node->getNodeType()->getConfiguration($reloadPageIfChangedConfigurationPath)) {
                // we render content directly as response of this operation, so we need to flush the caches
                $this->contentCacheFlusher->flushNodeAggregate(
                    $node->getContentStreamIdentifier(),
                    $node->getNodeAggregateIdentifier()
                );
                $this->reloadDocument($node);
            }

            // This might be needed to update node label and other things that we can calculate only on the server
            $updateNodeInfo = new UpdateNodeInfo();
            $updateNodeInfo->setNode($node);
            $this->feedbackCollection->add($updateNodeInfo);
        }
    }
}
