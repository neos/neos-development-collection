<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;
use Neos\Utility\Arrays;

/**
 * @api
 */
final readonly class ReferenceTypeConstraintsDeclaration implements \JsonSerializable
{
    /**
     * @param array<string,bool> $constrainedReferenceTypes indexed by reference type name, value whether it is allowed or not
     */
    private function __construct(
        public bool $isWildcardAllowed,
        public array $constrainedReferenceTypes,
    ) {
    }

    /**
     * @param array<string,bool> $constrainedReferenceTypes indexed by reference type name, value whether it is allowed or not
     */
    public static function create(
        bool $isWildcardAllowed,
        array $constrainedReferenceTypes,
    ): self {
        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            constrainedReferenceTypes: $constrainedReferenceTypes,
        );
    }

    /**
     * @param array<string,bool> $values
     */
    public static function fromArray(array $values): self
    {
        if (array_key_exists('*', $values)) {
            $isWildcardAllowed = $values['*'];
            unset($values['*']);
        } else {
            $isWildcardAllowed = false;
        }

        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            constrainedReferenceTypes: $values,
        );
    }

    public static function fromReferenceTypeConstraints(ReferenceTypeConstraints $referenceTypeConstraints): self
    {
        $constrainedReferenceTypes = [];
        foreach ($referenceTypeConstraints->explicitlyAllowedReferenceTypeNames as $explicitlyAllowedNodeTypeName) {
            $constrainedReferenceTypes[$explicitlyAllowedNodeTypeName->value] = true;
        }
        foreach ($referenceTypeConstraints->explicitlyDisallowedReferenceTypeNames as $explicitlyDisallowedNodeTypeName) {
            $constrainedReferenceTypes[$explicitlyDisallowedNodeTypeName->value] = false;
        }

        return new self(
            isWildcardAllowed: $referenceTypeConstraints->isWildcardAllowed,
            constrainedReferenceTypes: $constrainedReferenceTypes,
        );
    }

    public function mergeToSubType(self|ReferenceTypeConstraints $subTypeReferenceTypeConstraints): self
    {
        if ($subTypeReferenceTypeConstraints instanceof ReferenceTypeConstraints) {
            $subTypeReferenceTypeConstraints = self::fromReferenceTypeConstraints($subTypeReferenceTypeConstraints);
        }

        return new self(
            isWildcardAllowed: $subTypeReferenceTypeConstraints->isWildcardAllowed,
            constrainedReferenceTypes: Arrays::arrayMergeRecursiveOverrule(
                $this->constrainedReferenceTypes,
                $subTypeReferenceTypeConstraints->constrainedReferenceTypes,
            ),
        );
    }

    public function toReferenceTypeConstraints(): ReferenceTypeConstraints
    {
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];
        foreach ($this->constrainedReferenceTypes as $referenceTypeName => $isAllowed) {
            if ($isAllowed) {
                $explicitlyAllowedNodeTypeNames[] = $referenceTypeName;
            } else {
                $explicitlyDisallowedNodeTypeNames[] = $referenceTypeName;
            }
        }
        return ReferenceTypeConstraints::create(
            isWildcardAllowed: $this->isWildcardAllowed,
            includeAbstract: true,
            explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray($explicitlyAllowedNodeTypeNames),
            explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray($explicitlyDisallowedNodeTypeNames),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
