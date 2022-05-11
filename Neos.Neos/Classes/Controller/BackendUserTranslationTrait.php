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

namespace Neos\Neos\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;

/**
 * A trait to add backend translation based on the backend users settings
 */
trait BackendUserTranslationTrait
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Service\UserService
     */
    protected $userService;

    /**
     * Set the locale according to the user settings
     *
     * @return void
     */
    protected function initializeObject()
    {
        $this->localizationService->getConfiguration()->setCurrentLocale(
            new Locale($this->userService->getInterfaceLanguage())
        );
    }
}
