<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility;

use PHPStan\Reflection\ClassReflection;

final class ClassClassification
{
    private function __construct(
        public readonly bool $isInternal,
        public readonly bool $isApi,
    ) {
    }

    public static function fromClassReflection(ClassReflection $classReflection): self
    {
        if (!$classReflection->getResolvedPhpDoc()) {
            return new self(false, false);
        }
        $phpDocNode = $classReflection->getResolvedPhpDoc()->getPhpDocNodes()[0];

        $hasInternalAnnotation = count($phpDocNode->getTagsByName('@internal')) > 0;
        $hasApiAnnotation = count($phpDocNode->getTagsByName('@api')) > 0;

        return new self($hasInternalAnnotation, $hasApiAnnotation);
    }
}
