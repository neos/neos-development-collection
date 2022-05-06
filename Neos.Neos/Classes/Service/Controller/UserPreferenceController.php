<?php
namespace Neos\Neos\Service\Controller;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Neos\Domain\Service\UserService;

/**
 * Service Controller for user preferences
 */
class UserPreferenceController extends AbstractServiceController
{
    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $domainUserService;

    /**
     * @return string json encoded user preferences
     */
    public function indexAction()
    {
        $this->response->setContentType('application/json');
        return json_encode($this->domainUserService->getCurrentUser()->getPreferences()->getPreferences() ?: '{}');
    }

    /**
     * Update/adds a user preference
     *
     * @param string $key The key of the preference to update/add
     * @param string $value The value of the preference
     * @return void
     * @throws StopActionException
     */
    public function updateAction($key, $value)
    {
        // TODO: This should be done in an earlier stage (TypeConverter ?)
        if (strtolower($value) === 'false') {
            $value = false;
        } elseif (strtolower($value) === 'true') {
            $value = true;
        }

        $user = $this->domainUserService->getCurrentUser();
        $user->getPreferences()->set($key, $value);
        $this->domainUserService->updateUser($user);
        $this->throwStatus(204, 'User preferences have been updated');
    }
}
