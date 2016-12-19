<?php
namespace Neos\Neos\ViewHelpers\Backend;

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
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\Security\AccountRepository;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Service\UserService;
use Neos\Neos\Domain\Service\UserService as DomainUserService;
use Neos\Party\Domain\Model\Person;

/**
 * Render user initials for a given username
 *
 * This ViewHelper is *WORK IN PROGRESS* and *NOT STABLE YET*
 */
class UserInitialsViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * Render user initials or an abbreviated name for a given username. If the account was deleted, use the username as fallback.
     *
     * @param string $format Supported are "fullFirstName" and "initials"
     * @return string
     */
    public function render($format = 'initials')
    {
        if (!in_array($format, array('fullFirstName', 'initials', 'fullName'))) {
            throw new \InvalidArgumentException(sprintf('Format "%s" given to history:userInitials(), only supporting "fullFirstName", "initials" and "fullName".', $format), 1415705861);
        }

        $username = $this->renderChildren();

        /* @var $requestedUser Person */
        $requestedUser = $this->domainUserService->getUser($username);
        if ($requestedUser === null || $requestedUser->getName() === null) {
            return $username;
        }

        $currentUser = $this->userService->getBackendUser();
        if ($currentUser) {
            if ($currentUser === $requestedUser) {
                $translationHelper = new TranslationHelper();
                $you = $translationHelper->translate('you', null, [], 'Main', 'Neos.Neos');
            }
        }

        switch ($format) {
            case 'initials':
                return mb_substr($requestedUser->getName()->getFirstName(), 0, 1) . mb_substr($requestedUser->getName()->getLastName(), 0, 1);
            case 'fullFirstName':
                return isset($you) ? $you : $requestedUser->getName()->getFirstName() . ' ' . mb_substr($requestedUser->getName()->getLastName(), 0, 1) . '.';
            case 'fullName':
                return isset($you) ? $you : $requestedUser->getName()->getFullName();
        }
    }
}
