<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Security;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Neos\Service\ImpersonateService;

/**
 * An aspect which centralizes the logging of security relevant actions.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ImpersonateAspect
{
    /**
     * @var bool
     */
    protected bool $alreadyLoggedAuthenticateCall = false;

    /**
     * @Flow\Inject
     */
    protected ImpersonateService $impersonateService;

    /**
     * @Flow\After("within(Neos\Flow\Security\Authentication\AuthenticationManagerInterface) && method(.*->authenticate())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return void
     * @throws \Exception
     */
    public function logManagerAuthenticate(JoinPointInterface $joinPoint): void
    {
        /** @var AuthenticationManagerInterface $proxy */
        $proxy = $joinPoint->getProxy();

        if ($this->alreadyLoggedAuthenticateCall === true) {
            $this->alreadyLoggedAuthenticateCall = true;
            return;
        }
        /** @var ?Account $account */
        $account = $proxy->getSecurityContext()->getAccount();
        if ($account === null) {
            $this->alreadyLoggedAuthenticateCall = true;
            return;
        }

        if ($this->impersonateService->isActive()) {
            $impersonation = $this->impersonateService->getImpersonation();
            foreach ($proxy->getSecurityContext()->getAuthenticationTokens() as $token) {
                $token->setAccount($impersonation);
            }
        }

        $this->alreadyLoggedAuthenticateCall = true;
    }
}
