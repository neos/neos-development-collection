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

namespace Neos\Neos\Controller\Module;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;

/**
 * A trait to do easy backend module translations
 */
trait ModuleTranslationTrait
{
    #[Flow\Inject]
    protected Translator $translator;

    /**
     * @param array<int|string,mixed> $arguments
     */
    public function getModuleLabel(string $id, array $arguments = []): string
    {
        return $this->translator->translateById(
            $id,
            $arguments,
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: $id;
    }
}
