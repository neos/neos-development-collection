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

namespace Neos\Neos\Domain\Model;

final class EditPreviewMode
{
    protected function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly ?string $fusionPath,
        public readonly bool $isEditMode,
        public readonly bool $isPreviewMode
    ) {
    }

    /**
     * @param string $name
     * @param array{'title'?:string, 'fusionRenderingPath'?:string, 'isEditingMode'?:bool, 'isPreviewMode'?:bool} $configuration
     * @return self
     */
    public static function fromNameAndConfiguration(string $name, array $configuration): self
    {
        return new static(
            $name,
            $configuration['title'] ?? $name,
            $configuration['fusionRenderingPath'] ?? null,
            $configuration['isEditingMode'] ?? false,
            $configuration['isPreviewMode'] ?? false
        );
    }
}
