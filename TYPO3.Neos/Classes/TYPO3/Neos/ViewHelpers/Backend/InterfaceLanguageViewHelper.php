<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Service\UserService;

/**
 * ViewHelper for rendering the current backend users interface language.
 */
class InterfaceLanguageViewHelper extends AbstractViewHelper
{

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @return string The current backend users interface language
     */
    public function render()
    {
        return $this->userService->getInterfaceLanguage();
    }
}
