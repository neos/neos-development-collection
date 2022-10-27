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
 * BackendUser helper for translations in backend.
 */
class BackendUserHelper implements ProtectedContextAwareInterface
{
    private const FROM_EEL_DIRECTLY_CALLABLE_METHODS = [];

    #[Flow\Inject(lazy: false)]
    protected UserService $userService;

    /**
     * Returns the interface language the user selected. Will fall back to the default language defined in settings.
     *
     * @example Neos.BackendUser.interfaceLanguage
     */
    public function getInterfaceLanguage(): string
    {
        return $this->userService->getInterfaceLanguage();
    }

    public function allowsCallOfMethod($methodName)
    {
        // checks if method is allowed to be called from EEL directly or should use property access notation
        return in_array($methodName, self::FROM_EEL_DIRECTLY_CALLABLE_METHODS, true);
    }
}
