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

use Neos\Neos\Domain\Exception;

/**
 * Describes the mode in which the Neos interface is rendering currently,
 * mainly distinguishing between edit and preview modes currently.
 */
final class RenderingMode
{
    public const FRONTEND = 'frontend';

    /**
     * @param array<string,mixed> $options
     */
    private function __construct(
        public readonly string $name,
        public readonly bool $isEdit,
        public readonly bool $isPreview,
        public readonly string $title,
        public readonly string $fusionPath,
        public readonly array $options
    ) {
    }

    /**
     * Creates a rendering mode from its configuration
     *
     * @param string $modeName
     * @param array<string,mixed> $configuration
     */
    public static function createFromConfiguration(string $modeName, array $configuration): RenderingMode
    {
        if ($modeName === RenderingMode::FRONTEND) {
            throw new Exception(
                'Cannot instantiate system rendering mode "frontend" from configuration.'
                . ' Please use RenderingMode::createFrontend().',
                1694802951840
            );
        }
        return new self(
            $modeName,
            $configuration['isEditingMode'] ?? false,
            $configuration['isPreviewMode'] ?? false,
            $configuration['title'] ?? $modeName,
            $configuration['fusionRenderingPath'] ?? '',
            $configuration['options'] ?? [],
        );
    }

    /**
     * Creates the system integrated rendering mode 'frontend'
     */
    public static function createFrontend(): RenderingMode
    {
        return new self(
            RenderingMode::FRONTEND,
            false,
            false,
            'Frontend',
            '',
            []
        );
    }
}
