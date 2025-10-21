<?php

declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\AuthenticationProviderManager;
use Neos\Flow\Security\Authentication\Provider\TestingProvider;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Step implementations and helper for Flow Security related tests inside Neos.Neos
 *
 * TODO this should wander to the Flow core at some point.
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait FlowSecurityTrait
{
    private bool $flowSecurity_securityEnabled = false;

    private ?TestingProvider $flowSecurity_testingProvider = null;

    private ?ActionRequest $flowSecurity_mockActionRequest = null;

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    abstract protected function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    final public function resetFlowSecurity(): void
    {
        $this->flowSecurity_securityEnabled = false;

        $policyService = $this->getObject(PolicyService::class);
        // reset the $policyConfiguration to the default (fetched from the original ConfigurationManager)
        $this->getObject(PolicyService::class)->reset(); // TODO also reset privilegeTargets in ->reset()
        ObjectAccess::setProperty($policyService, 'privilegeTargets', [], true);
        $policyService->injectConfigurationManager($this->getObject(ConfigurationManager::class));

        $securityContext = $this->getObject(SecurityContext::class);
        $securityContext->clearContext();
        // todo add setter! Also used in FunctionalTestCase https://github.com/neos/flow-development-collection/commit/b9c89e3e08649cbb5366cb769b2f79b0f13bd68e
        ObjectAccess::setProperty($securityContext, 'authorizationChecksDisabled', true, true);
        $this->getObject(PrivilegeManagerInterface::class)->reset();
    }

    final protected function enableFlowSecurity(): void
    {
        if ($this->flowSecurity_securityEnabled === true) {
            return;
        }

        $tokenAndProviderFactory = $this->getObject(TokenAndProviderFactoryInterface::class);

        $this->flowSecurity_testingProvider = $tokenAndProviderFactory->getProviders()['TestingProvider'];

        $securityContext = $this->getObject(SecurityContext::class);
        $securityContext->clearContext(); // enable authorizationChecks
        $httpRequest = $this->getObject(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost/');
        $this->flowSecurity_mockActionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $securityContext->setRequest($this->flowSecurity_mockActionRequest);
        $this->flowSecurity_securityEnabled = true;
    }

    final protected function authenticateAccount(Account $account): void
    {
        $this->enableFlowSecurity();
        $this->flowSecurity_testingProvider->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $this->flowSecurity_testingProvider->setAccount($account);

        $securityContext = $this->getObject(SecurityContext::class);
        $securityContext->clearContext();
        $securityContext->setRequest($this->flowSecurity_mockActionRequest);
        $this->getObject(AuthenticationProviderManager::class)->authenticate();
    }

    /**
     * @Given The following additional policies are configured:
     */
    final public function theFollowingAdditionalPoliciesAreConfigured(PyStringNode $policies): void
    {
        $policyService = $this->getObject(PolicyService::class);

        $mergedPolicyConfiguration = Arrays::arrayMergeRecursiveOverrule(
            $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_POLICY),
            Yaml::parse($policies->getRaw())
        );

        // if we de-initialise the PolicyService and set a new $policyConfiguration (by injecting a stub ConfigurationManager which will be used)
        // we can change the roles and privileges at runtime :D
        $policyService->reset(); // TODO also reset privilegeTargets in ->reset()
        ObjectAccess::setProperty($policyService, 'privilegeTargets', [], true);
        $policyService->injectConfigurationManager(new class ($mergedPolicyConfiguration) extends ConfigurationManager
        {
            public function __construct(
                private array $mergedPolicyConfiguration
            ) {
            }

            public function getConfiguration(string $configurationType, ?string $configurationPath = null)
            {
                Assert::assertSame(ConfigurationManager::CONFIGURATION_TYPE_POLICY, $configurationType);
                Assert::assertSame(null, $configurationPath);
                return $this->mergedPolicyConfiguration;
            }
        });
    }

    /**
     * @When I am not authenticated
     */
    final public function iAmNotAuthenticated(): void
    {
        $this->flowSecurity_testingProvider->reset();
        $securityContext = $this->getObject(SecurityContext::class);
        $securityContext->clearContext();
        $securityContext->setRequest($this->flowSecurity_mockActionRequest);
        $this->getObject(AuthenticationProviderManager::class)->authenticate();
    }
}
