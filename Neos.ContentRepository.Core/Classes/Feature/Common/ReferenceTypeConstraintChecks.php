<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes\NodeTypeFiltersDeclaration;
use Neos\ContentRepository\Core\NodeType\ConstraintCheck;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Property\Property;

/**
 * @internal implementation details of command handlers;
 * Constraint checks only operate on the ReferenceTypes, not on the content graph
 */
trait ReferenceTypeConstraintChecks
{
    protected function requireReferenceType(
        ReferenceTypeName $referenceTypeName,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
    ): ReferenceType {
        return $this->getReferenceTypeGraph()->getReferenceType($referenceTypeName)
            ?: throw ReferenceCannotBeSet::becauseTheReferenceTypeDoesNotExist(
                $referenceName,
                $nodeTypeName,
                $referenceTypeName,
            );
    }

    protected function requireReferenceTypeToNotBeAbstract(
        ReferenceType $referenceType,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
    ): void {
        if ($referenceType->isAbstract) {
            throw ReferenceCannotBeSet::becauseTheReferenceTypeIsAbstract(
                $referenceName,
                $nodeTypeName,
                $referenceType->name
            );
        }
    }

    protected function requireNodeTypeToAllowReferencesOfType(
        NodeType $nodeType,
        ReferenceTypeName $referenceTypeName,
        ReferenceName $referenceName,
    ): void {
        if (
            !$this->referenceTypeGraph->getReferenceTypesMatchingConstraints(
                $nodeType->getReferenceTypeConstraints($referenceName),
            )->hasElementNamed($referenceTypeName)
        ) {
            throw ReferenceCannotBeSet::becauseTheReferenceTypeIsDisallowedByTheSourceNodeType(
                $referenceName,
                $nodeType->name,
                $referenceTypeName,
            );
        }
    }

    protected function requireReferenceTypeToAllowSourceNodesOfType(
        NodeType $sourceNodeType,
        ReferenceType $referenceType,
        ReferenceName $referenceName,
    ): void {
        if (!ConstraintCheck::create(
            NodeTypeFiltersDeclaration::fromNodeTypeFilters($referenceType->constraints->sourceNodeTypes)->filteredNodeTypes
        )->isNodeTypeAllowed($sourceNodeType)) {
            throw ReferenceCannotBeSet::becauseTheReferenceTypeDisallowsTheSourceNodeType(
                $referenceName,
                $sourceNodeType->name,
                $referenceType->name,
            );
        }
    }

    protected function requireReferenceTypeToAllowTargetNodesOfType(
        NodeType $targetNodeType,
        ReferenceType $referenceType,
        ReferenceName $referenceName,
    ): void {
        if (!ConstraintCheck::create(
            NodeTypeFiltersDeclaration::fromNodeTypeFilters($referenceType->constraints->targetNodeTypes)->filteredNodeTypes
        )->isNodeTypeAllowed($targetNodeType)) {
            throw ReferenceCannotBeSet::becauseTheReferenceTypeDisallowsTheTargetNodeType(
                $referenceName,
                $targetNodeType->name,
                $referenceType->name,
            );
        }
    }

    protected function requireReferenceTypeProperty(
        ReferenceType $referenceType,
        PropertyName $propertyName,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeName,
    ): Property {
        return $referenceType->getProperty($propertyName)
            ?: throw ReferenceCannotBeSet::becauseTheReferenceTypeDoesNotDeclareTheProperty(
                $referenceName,
                $nodeTypeName,
                $referenceType->name,
                $propertyName,
            );
    }
}
