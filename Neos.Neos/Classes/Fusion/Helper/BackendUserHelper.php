<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\UserService;

/**
 * BackendUser helper for translations in the backend
 */
class BackendUserHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject(lazy: false)]
    protected UserService $userService;

    /**
     * The interface language the user selected
     * Falls back to the default language defined in the settings
     *
     * Example::
     *
     *     Translation.id("mh").locale(Neos.BackendUser.interfaceLanguage()).translate()
     *
     */
    public function interfaceLanguage(): string
    {
        return $this->userService->getInterfaceLanguage();
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
