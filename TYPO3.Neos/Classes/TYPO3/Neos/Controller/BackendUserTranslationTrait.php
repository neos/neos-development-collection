<?php
namespace TYPO3\Neos\Controller;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Locale;

/**
 * A trait to add backend translation based on the backend users settings
 */
trait BackendUserTranslationTrait
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $_localizationService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\UserService
     */
    protected $_userService;

    /**
     * Set the locale according to the user settings
     *
     * @return void
     */
    protected function initializeObject()
    {
        $this->_localizationService->getConfiguration()->setCurrentLocale(new Locale($this->_userService->getInterfaceLanguage()));
    }
}
