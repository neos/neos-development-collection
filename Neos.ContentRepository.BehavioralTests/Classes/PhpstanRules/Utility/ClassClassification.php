<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\PhpstanRules\Utility;

use PHPStan\Reflection\ClassReflection;

final readonly class ClassClassification
{
    private function __construct(
        public bool $isInternal,
        public bool $isApi,
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
