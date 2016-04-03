<?php
namespace TYPO3\Neos\Service\Controller;

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
use TYPO3\Neos\Service\UserService;

/**
 * Service Controller for user preferences
 */
class UserPreferenceController extends AbstractServiceController
{
    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @return string json encoded user preferences
     */
    public function indexAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        return json_encode($this->userService->getBackendUser()->getPreferences()->getPreferences());
    }

    /**
     * Update/adds a user preference
     *
     * @param string $key The key of the preference to update/add
     * @param string $value The value of the preference
     * @return void
     */
    public function updateAction($key, $value)
    {
        // TODO: This should be done in an earlier stage (TypeConverter ?)
        if (strtolower($value) === 'false') {
            $value = false;
        } elseif (strtolower($value) === 'true') {
            $value = true;
        }

        $user = $this->userService->getBackendUser();
        $user->getPreferences()->set($key, $value);
        $this->partyRepository->update($user);
        $this->throwStatus(204, 'User preferences have been updated');
    }
}
