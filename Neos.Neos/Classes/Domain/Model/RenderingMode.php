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

use Neos\Utility\ObjectAccess;

/**
 * Describes the mode in which the Neos interface is rendering currently,
 * mainly distinguishing between edit and preview modes currently.
 */
class RenderingMode
{
    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $isEdit,
        public readonly bool $isPreview,
        public readonly string $title,
        public readonly string $fusionPath,
        public readonly array $options
    ) {
    }

    /**
     * Creates an UserInterfaceMode object by configuration
     *
     * @param string $modeName
     * @param array<string,mixed> $configuration
     */
    public static function createFromConfiguration(string $modeName, array $configuration): RenderingMode
    {
        $mode = new RenderingMode(
            $modeName,
            $configuration['isEditingMode'] ?? false,
            $configuration['isPreviewMode'] ?? false,
            $configuration['title'] ?? $modeName,
            $configuration['fusionRenderingPath'] ?? '',
            $configuration['options'] ?? [],
        );
        return $mode;
    }

    /**
     * Creates the live User interface mode
     */
    public static function createFrontend(): RenderingMode
    {
        return new RenderingMode(
            'frontend',
            false,
            false,
            'Frontend',
            '',
            []
        );
    }
}
