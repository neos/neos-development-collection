<?php
declare(strict_types=1);

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
    public function impersonate(Account $account): void
    {
        $currentAccount = $this->securityContext->getAccount();
        $this->session->putData('OriginalIdentity', $this->persistenceManager->getIdentifierByObject($currentAccount));

        $this->refreshTokens($account);

        $this->session->putData('Impersonate', $this->persistenceManager->getIdentifierByObject($account));
    }

    /**
     * @return void
     * @throws SessionNotStartedException
     */
    public function restoreOriginalIdentity(): void
    {
        $account = $this->getOriginalIdentity();
        $this->refreshTokens($account);
        $this->session->putData('Impersonate', null);
    }

    /**
     * @return Account|null
     * @throws SessionNotStartedException
     */
    public function getImpersonation(): ?Account
    {
        if ($this->session->getData('Impersonate') !== null) {
            return $this->persistenceManager->getObjectByIdentifier($this->session->getData('Impersonate'), Account::class);
        }
        return null;
    }

    /**
     * @return bool
     * @throws SessionNotStartedException
     */
    public function isActive(): bool
    {
        return $this->getImpersonation() instanceof Account;
    }

    /**
     * @return Account|null
     */
    public function getCurrentUser(): ?Account
    {
        return $this->securityContext->getAccount();
    }

    /**
     * @return Account|null
     * @throws SessionNotStartedException
     */
    public function getOriginalIdentity(): ?Account
    {
        if ($this->session->getData('OriginalIdentity') !== null) {
            return $this->persistenceManager->getObjectByIdentifier($this->session->getData('OriginalIdentity'), Account::class);
        }
        return $this->securityContext->getAccount();
    }

    /**
     * @return array
     * @throws SessionNotStartedException
     */
    public function getOriginalIdentityRoles(): array
    {
        $originalAccount = $this->getOriginalIdentity();
        $roles = $originalAccount ? $originalAccount->getRoles() : [];
        foreach ($roles as $role) {
            foreach ($this->policyService->getAllParentRoles($role) as $parentRole) {
                if (!in_array($parentRole, $roles, true)) {
                    $roles[$parentRole->getIdentifier()] = $parentRole;
                }
            }
        }
        return $roles;
    }

    /**
     * @param Account|null $account
     * @return void
     */
    protected function refreshTokens(Account $account = null): void
    {
        if ($account === null) {
            return;
        }

        $tokens = $this->securityContext->getAuthenticationTokens();
        foreach ($tokens as $token) {
            $token->setAccount($account);
        }
    }
}
