<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * @api to be implemented by adapters and to be evaluated in userland
 */
interface ReferenceTypeGraphInterface
{
    public function getReferenceType(ReferenceTypeName $referenceTypeName): ?ReferenceType;

    public function getDeclaredSuperTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList;

    public function getSuperTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList;

    public function getDeclaredSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList;

    public function getSubTypeNames(ReferenceTypeName $referenceTypeName): ReferenceTypeNameList;
}
