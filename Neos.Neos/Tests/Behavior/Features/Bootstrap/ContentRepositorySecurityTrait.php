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

use Behat\Hook\BeforeScenario;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\TestSuite\Fakes\FakeAuthProvider;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Provider\TestingProvider;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Security\ContentRepositoryAuthProvider\ContentRepositoryAuthProviderFactory;
use PHPUnit\Framework\Assert;

/**
 * Step implementations and helper for Content Repository Security related tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait ContentRepositorySecurityTrait
{
    use CRBehavioralTestsSubjectProvider;
    use ExceptionsTrait;
    use FlowSecurityTrait;

    private bool $crSecurity_flowSecurityEnabled = false;
    private bool $crSecurity_contentRepositorySecurityEnabled = false;

    private ?TestingProvider $crSecurity_testingProvider = null;

    private ?ActionRequest $crSecurity_mockActionRequest = null;

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    public function resetContentRepositorySecurity(): void
    {
        FakeAuthProvider::resetAuthProvider();
        $this->crSecurity_contentRepositorySecurityEnabled = false;
    }

    private function enableContentRepositorySecurity(): void
    {
        if ($this->crSecurity_contentRepositorySecurityEnabled === true) {
            return;
        }
        $contentRepositoryAuthProviderFactory = $this->getObject(ContentRepositoryAuthProviderFactory::class);
        $contentGraphReadModel = $this->getContentGraphReadModel();
        $contentRepositoryAuthProvider = $contentRepositoryAuthProviderFactory->build($this->currentContentRepository->id, $contentGraphReadModel);

        FakeAuthProvider::replaceAuthProvider($contentRepositoryAuthProvider);
        $this->crSecurity_contentRepositorySecurityEnabled = true;
    }

    /**
     * @Given content repository security is enabled
     */
    public function contentRepositorySecurityIsEnabled(): void
    {
        $this->enableFlowSecurity();
        $this->enableContentRepositorySecurity();
    }

    /**
     * @When I am authenticated as :username
     */
    public function iAmAuthenticatedAs(string $username): void
    {
        $user = $this->getObject(UserService::class)->getUser($username);
        $this->authenticateAccount($user->getAccounts()->first());
    }

    /**
     * @When I access the content graph for workspace :workspaceName
     */
    public function iAccessesTheContentGraphForWorkspace(string $workspaceName): void
    {
        $this->tryCatchingExceptions(fn () => $this->currentContentRepository->getContentGraph(WorkspaceName::fromString($workspaceName)));
    }

    /**
     * @Then I should not be able to read node :nodeAggregateId
     */
    public function iShouldNotBeAbleToReadNode(string $nodeAggregateId): void
    {
        $node = $this->currentContentRepository->getContentSubgraph($this->currentWorkspaceName, $this->currentDimensionSpacePoint)->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
        if ($node !== null) {
            Assert::fail(sprintf('Expected node "%s" to be inaccessible but it was loaded', $nodeAggregateId));
        }
    }

    /**
     * @Then I should be able to read node :nodeAggregateId
     */
    public function iShouldBeAbleToReadNode(string $nodeAggregateId): void
    {
        $node = $this->currentContentRepository->getContentSubgraph($this->currentWorkspaceName, $this->currentDimensionSpacePoint)->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
        if ($node === null) {
            Assert::fail(sprintf('Expected node "%s" to be accessible but it could not be loaded', $nodeAggregateId));
        }
    }
}
