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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Neos\Service\UserService;

/**
 * BackendUser helper for translations in the backend
 */
class BackendHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject(lazy: false)]
    protected UserService $userService;

    /**
     * The interface language the user selected or the default language defined in the settings
     * Formatted as {@see \Neos\Flow\I18n\Locale} identifier, eg "de", "en", ...
     *
     * Example::
     *
     *     Translation.id("mh").locale(Neos.Backend.interfaceLanguage()).translate()
     *
     */
    public function interfaceLanguage(): string
    {
        $currentUser = $this->userService->getBackendUser();
        assert($currentUser !== null, "No backend user");
        return $this->userService->getInterfaceLanguage();
    }

    public function isEditMode(ActionRequest $request): bool
    {
        return ($request->getControllerPackageKey() === 'Neos.Neos'
            && $request->getControllerName() === "Frontend\Node"
            && $request->getControllerActionName() === 'edit'
        );
    }

    public function isPreviewMode(ActionRequest $request): bool
    {
        return ($request->getControllerPackageKey() === 'Neos.Neos'
            && $request->getControllerName() === "Frontend\Node"
            && $request->getControllerActionName() === 'preview'
        );
    }

    public function editPreviewModeCacheIdentifier(ActionRequest $request): string
    {
        if ($request->getControllerPackageKey() === 'Neos.Neos'
            && $request->getControllerName() === "Frontend\Node"
            && ($request->getControllerActionName() === 'edit' || $request->getControllerActionName() === 'preview')
        ) {
            return $request->getControllerActionName() . ($request->hasArgument('editPreviewMode') ? ':' . $request->getArgument('editPreviewMode') : '');
        } else {
            return "";
        }
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
