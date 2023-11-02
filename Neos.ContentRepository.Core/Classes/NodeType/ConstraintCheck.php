<?php

namespace Neos\ContentRepository\Core\NodeType;

/**
 * Performs node type constraint checks against a given set of constraints
 * @internal
 */
final readonly class ConstraintCheck
{
    /**
     * @param array<string,bool> $constraints
     */
    private function __construct(
        private array $constraints
    ) {
    }

    /**
     * @param array<string,bool> $constraints
     */
    public static function create(array $constraints): self
    {
        return new self($constraints);
    }

    public function isNodeTypeAllowed(NodeType $nodeType): bool
    {
        $directConstraintsResult = $this->isNodeTypeAllowedByDirectConstraints($nodeType);
        if ($directConstraintsResult !== null) {
            return $directConstraintsResult;
        }

        $inheritanceConstraintsResult = $this->isNodeTypeAllowedByInheritanceConstraints($nodeType);
        if ($inheritanceConstraintsResult !== null) {
            return $inheritanceConstraintsResult;
        }

        if (isset($this->constraints['*'])) {
            return (bool)$this->constraints['*'];
        }

        return false;
    }

    /**
     * @return boolean|null true if the passed $nodeType is allowed by the $constraints, null if couldn't be decided
     */
    protected function isNodeTypeAllowedByDirectConstraints(NodeType $nodeType): ?bool
    {
        if ($this->constraints === []) {
            return true;
        }

        if (
            array_key_exists($nodeType->name->value, $this->constraints)
            && $this->constraints[$nodeType->name->value] === true
        ) {
            return true;
        }

        if (
            array_key_exists($nodeType->name->value, $this->constraints)
            && $this->constraints[$nodeType->name->value] === false
        ) {
            return false;
        }

        return null;
    }

    /**
     * This method loops over the constraints and finds node types that the given node type inherits from. For all
     * matched super types, their super types are traversed to find the closest super node with a constraint which
     * is used to evaluated if the node type is allowed. It finds the closest results for true and false, and uses
     * the distance to choose which one wins (lowest). If no result is found the node type is allowed.
     *
     * @return ?boolean (null if no constraint matched)
     */
    protected function isNodeTypeAllowedByInheritanceConstraints(NodeType $nodeType): ?bool
    {
        $constraintDistanceForTrue = null;
        $constraintDistanceForFalse = null;
        foreach ($this->constraints as $superType => $constraint) {
            if ($nodeType->isOfType($superType)) {
                $distance = $this->traverseSuperTypes($nodeType, $superType, 0);

                if (
                    $constraint === true
                    && ($constraintDistanceForTrue === null || $constraintDistanceForTrue > $distance)
                ) {
                    $constraintDistanceForTrue = $distance;
                }
                if (
                    $constraint === false
                    && ($constraintDistanceForFalse === null || $constraintDistanceForFalse > $distance)
                ) {
                    $constraintDistanceForFalse = $distance;
                }
            }
        }

        if ($constraintDistanceForTrue !== null && $constraintDistanceForFalse !== null) {
            return $constraintDistanceForTrue < $constraintDistanceForFalse;
        }

        if ($constraintDistanceForFalse !== null) {
            return false;
        }

        if ($constraintDistanceForTrue !== null) {
            return true;
        }

        return null;
    }

    /**
     * This method traverses the given node type to find the first super type that matches the constraint node type.
     * In case the hierarchy has more than one way of finding a path to the node type it's not taken into account,
     * since the first matched is returned. This is accepted on purpose for performance reasons and due to the fact
     * that such hierarchies should be avoided.
     *
     * Returns null if no NodeType matched
     */
    protected function traverseSuperTypes(
        NodeType $currentNodeType,
        string $constraintNodeTypeName,
        int $distance
    ): ?int {
        if ($currentNodeType->name->value === $constraintNodeTypeName) {
            return $distance;
        }

        $distance++;
        foreach ($currentNodeType->getDeclaredSuperTypes() as $superType) {
            $result = $this->traverseSuperTypes($superType, $constraintNodeTypeName, $distance);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
