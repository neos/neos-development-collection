<?php

namespace Neos\Neos\Service;

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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;

/**
 * Impersonate Service
 */
class ImpersonateService
{
    /**
     * @var Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var SessionInterface
     * @Flow\Inject
     */
    protected $session;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var PolicyService
     * @Flow\Inject
     */
    protected $policyService;

    /**
     * @param Account $account
     * @return void
     * @throws SessionNotStartedException
     */
    public function impersonate($account): void
    {
        $this->session->putData('OriginalIdentity', $this->persistenceManager->getIdentifierByObject($this->securityContext->getAccount()));

        $tokens = $this->securityContext->getAuthenticationTokens();
        foreach ($tokens as $token) {
            $token->setAccount($account);
        }

        $this->session->putData('Impersonate', $this->persistenceManager->getIdentifierByObject($account));
    }

    public function undoImpersonate(): void
    {
        $this->session->putData('Impersonate', null);
    }

    public function getImpersonation(): ?Account
    {
        if ($this->session->getData('Impersonate') !== null) {
            return $this->persistenceManager->getObjectByIdentifier($this->session->getData('Impersonate'), Account::class);
        }
        return null;
    }

    public function isActive()
    {
        return $this->getImpersonation() instanceof Account;
    }

    public function getCurrentUser(): ?Account
    {
        return $this->securityContext->getAccount();
    }

    public function getOriginalIdentity(): ?Account
    {
        if ($this->session->getData('OriginalIdentity') !== null) {
            return $this->persistenceManager->getObjectByIdentifier($this->session->getData('OriginalIdentity'), Account::class);
        }
        return $this->securityContext->getAccount();
    }

    public function getOriginalIdentityRoles(): array
    {
        $roles = $this->getOriginalIdentity()->getRoles();
        foreach ($roles as $role) {
            foreach ($this->policyService->getAllParentRoles($role) as $parentRole) {
                if (!in_array($parentRole, $roles)) {
                    $roles[$parentRole->getIdentifier()] = $parentRole;
                }
            }
        }
        return $roles;
    }
}
