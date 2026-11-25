<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceMetadataDeclarationInterface;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Undeclared;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class ReferenceLabelMetadataDeclaration implements ReferenceMetadataDeclarationInterface
{
    use GenericReferenceTypeMetadata;

    /**
     * @param class-string<ReferenceLabelGeneratorInterface>|Undeclared $generatorClass
     */
    public function __construct(
        public string|Undeclared $label,
        public string|Undeclared $generatorClass,
    ) {
    }

    public static function tryExtractFromArray(array &$declaration): ?static
    {
        $result = null;

        if (array_key_exists('label', $declaration)) {
            if (is_string($declaration['label'])) {
                $result = new self(
                    label: $declaration['label'],
                    generatorClass: Undeclared::INSTANCE,
                );
            } elseif (is_array($declaration['label'])) {
                $generatorClassName = $declaration['label']['generatorClass'] ?? null;
                if (
                    $generatorClassName
                    && class_exists($generatorClassName)
                    && in_array(ReferenceLabelGeneratorInterface::class, class_implements($generatorClassName))
                ) {
                    /** @var class-string<ReferenceLabelGeneratorInterface> $generatorClassName */
                    $result = new self(
                        label: Undeclared::INSTANCE,
                        generatorClass: $generatorClassName,
                    );
                }
            }
            unset($declaration['label']);
        }

        return $result;
    }

    public function toMetadata(): ReferenceLabelMetadata
    {
        return ReferenceLabelMetadata::create(
            label: $this->label instanceof Undeclared ? null : $this->label,
            generatorClassName: $this->generatorClass instanceof Undeclared ? null : $this->generatorClass,
        );
    }
}
