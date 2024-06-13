<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Neos\Neos\FrontendRouting\NodeAddress;
use PHPUnit\Framework\Assert;

/**
 * Browser related Behat steps
 *
 * Note this trait is impure see {@see self::setupBrowserForEveryScenario()}!
 *  It sets up a {@see FunctionalTestRequestHandler} as {@see \Neos\Flow\Core\Bootstrap::getActiveRequestHandler()}.
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait BrowserTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @var \Neos\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    public function setupBrowserForEveryScenario()
    {
        // we reset the security context at the beginning of every scenario; such that we start with a clean session at
        // every scenario and SHARE the session throughout the scenario!
        $this->getObject(\Neos\Flow\Security\Context::class)->clearContext();

        $this->browser = new \Neos\Flow\Http\Client\Browser();
        $this->browser->setRequestEngine(new \Neos\Neos\Testing\CustomizedInternalRequestEngine());
        $bootstrap = $this->getObject(\Neos\Flow\Core\Bootstrap::class);

        $requestHandler = new \Neos\Flow\Tests\FunctionalTestRequestHandler($bootstrap);
        $serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $request = $serverRequestFactory->createServerRequest('GET', 'http://localhost/flow/test');
        $requestHandler->setHttpRequest($request);
        $bootstrap->setActiveRequestHandler($requestHandler);
    }

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $currentResponse;

    /**
     * @var string
     */
    protected $currentResponseContents;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $currentRequest;

    /**
     * @var NodeAddress[]
     */
    private $currentNodeAddresses;

    /**
     * @param string|null $alias
     * @return \Neos\Neos\FrontendRouting\NodeAddress
     */
    protected function getCurrentNodeAddress(string $alias = null): NodeAddress
    {
        if ($alias === null) {
            $alias = 'DEFAULT';
        }
        return $this->currentNodeAddresses[$alias];
    }

    /**
     * @return \Neos\Neos\FrontendRouting\NodeAddress[]
     */
    public function getCurrentNodeAddresses(): array
    {
        return $this->currentNodeAddresses;
    }

    /**
     * @Given /^I get the node address for node aggregate "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $rawNodeAggregateId
     * @param string $alias
     */
    public function iGetTheNodeAddressForNodeAggregate(string $rawNodeAggregateId, $alias = 'DEFAULT')
    {
        $subgraph = $this->currentContentRepository->getContentGraph($this->currentWorkspaceName)->getSubgraph($this->currentDimensionSpacePoint, $this->currentVisibilityConstraints);
        $nodeAggregateId = NodeAggregateId::fromString($rawNodeAggregateId);
        $node = $subgraph->findNodeById($nodeAggregateId);
        Assert::assertNotNull($node, 'Did not find a node with aggregate id "' . $nodeAggregateId->value . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->currentContentStreamId,
            $this->currentDimensionSpacePoint,
            $nodeAggregateId,
            $this->currentWorkspaceName,
        );
    }

    /**
     * @Then /^I get the node address for the node at path "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $serializedNodePath
     * @param string $alias
     * @throws Exception
     */
    public function iGetTheNodeAddressForTheNodeAtPath(string $serializedNodePath, $alias = 'DEFAULT')
    {
        $subgraph = $this->currentContentRepository->getContentGraph($this->currentWorkspaceName)->getSubgraph($this->currentDimensionSpacePoint, $this->currentVisibilityConstraints);
        if (!$this->getRootNodeAggregateId()) {
            throw new \Exception('ERROR: rootNodeAggregateId needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $node = $subgraph->findNodeByPath(NodePath::fromString($serializedNodePath), $this->getRootNodeAggregateId());
        Assert::assertNotNull($node, 'Did not find a node at path "' . $serializedNodePath . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->currentContentStreamId,
            $this->currentDimensionSpacePoint,
            $node->aggregateId,
            $this->currentWorkspaceName,
        );
    }

    /**
     * @When /^I visit "([^"]*)"$/
     */
    public function iVisit($uriPath)
    {
        if (strpos($uriPath, 'CURRENT_NODE_ADDRESS') !== false) {
            $uriPath = str_replace('CURRENT_NODE_ADDRESS', $this->getCurrentNodeAddress()->serializeForUri(), $uriPath);
        }
        $this->currentResponse = $this->browser->request(new Uri('http://localhost' . $uriPath));
        $this->currentResponseContents = $this->currentResponse->getBody()->getContents();
        $this->currentRequest = $this->browser->getLastRequest();
    }

    /**
     * @Then /^the content of the page contains "([^"]*)"$/
     */
    public function theContentOfThePageContains($expectedString)
    {
        Assert::assertContains($expectedString, $this->currentResponseContents);
    }

    /**
     * @Then /^the content of the page does not contain "([^"]*)"$/
     */
    public function theContentOfThePageDoesNotContain($expectedString)
    {
        Assert::assertNotContains($expectedString, $this->currentResponseContents);
    }

    /**
     * @Then /^the URL path is "([^"]*)"$/
     */
    public function theUrlIs($expectedUrlPath)
    {
        $actual = $this->currentRequest->getUri()->getPath();
        Assert::assertEquals($expectedUrlPath, $actual, 'URL Paths do not match. Expected: ' . $expectedUrlPath . '; Actual: ' . $actual);
    }

    /**
     * @Given /^I am logged in as "([^"]*)" "([^"]*)"$/
     */
    public function iShouldBeLoggedInAs($user, $password)
    {
        $this->browser->request('http://localhost/neos/login', 'POST', [
            '__authentication' => [
                'Neos' => [
                    'Flow' => [
                        'Security' => [
                            'Authentication' => [
                                'Token' => [
                                    'UsernamePassword' => [
                                        'username' => $user,
                                        'password' => $password
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    protected function replacePlaceholders($nodeAddressString)
    {
        $nodeAddresses = $this->getCurrentNodeAddresses();
        foreach ($nodeAddresses as $alias => $nodeAddress) {
            /* @var $nodeAddress \Neos\Neos\FrontendRouting\NodeAddress */
            $nodeAddressString = str_replace($alias, $nodeAddress->serializeForUri(), $nodeAddressString);
        }

        return $nodeAddressString;
    }

    /**
     * @When /^I send the following changes:$/
     */
    public function iSendTheFollowingChanges(TableNode $changeDefinition)
    {
        $this->getObject(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        $changes = [];
        foreach ($changeDefinition->getHash() as $singleChange) {
            $payload = json_decode($this->replacePlaceholders($singleChange['Payload']), true);
            Assert::assertNotNull($payload, "The following string was no valid JSON: " . $this->replacePlaceholders($singleChange['Payload']));
            $changes[] = [

                'type' => $singleChange['Type'],
                'subject' => $this->replacePlaceholders($singleChange['Subject Node Address']),
                'payload' => $payload
            ];
        }

        $server = [
            'HTTP_X_FLOW_CSRFTOKEN' => $this->getObject(\Neos\Flow\Security\Context::class)->getCsrfProtectionToken(),
        ];
        $this->currentResponse = $this->browser->request('http://localhost/neos/ui-services/change', 'POST', ['changes' => $changes], [], $server);
        $this->currentResponseContents = $this->currentResponse->getBody()->getContents();
        $this->currentRequest = $this->browser->getLastRequest();
        Assert::assertEquals(200, $this->currentResponse->getStatusCode(), 'Status code wrong. Full response was: ' . $this->currentResponseContents);
    }

    /**
     * @When /^I publish the following nodes to "(.*)" workspace:$/
     */
    public function iPublishTheFollowingNodes(string $targetWorkspaceName, TableNode $nodesToPublish)
    {
        $this->getObject(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        $nodeContextPaths = [];
        foreach ($nodesToPublish->getHash() as $singleChange) {
            $nodeContextPaths[] = $this->replacePlaceholders($singleChange['Subject Node Address']);
        }

        $server = [
            'HTTP_X_FLOW_CSRFTOKEN' => $this->getObject(\Neos\Flow\Security\Context::class)->getCsrfProtectionToken(),
        ];
        $payload = [
            'nodeContextPaths' => $nodeContextPaths,
            'targetWorkspaceName' => $targetWorkspaceName
        ];

        $this->currentResponse = $this->browser->request('http://localhost/neos/ui-services/publish', 'POST', $payload, [], $server);
        $this->currentResponseContents = $this->currentResponse->getBody()->getContents();
        $this->currentRequest = $this->browser->getLastRequest();
        Assert::assertEquals(200, $this->currentResponse->getStatusCode(), 'Status code wrong. Full response was: ' . $this->currentResponseContents);
    }

    /**
     * @Then /^the feedback contains "([^"]*)"$/
     * @throws JsonException
     */
    public function theFeedbackContains($feedbackType)
    {
        $body = json_decode($this->currentResponseContents, true, 512, JSON_THROW_ON_ERROR);
        foreach ($body['feedbacks'] as $feedback) {
            if ($feedback['type'] === $feedbackType) {
                Assert::assertTrue(true, 'Feedback found');
                return;
            }
        }
        Assert::assertTrue(false, 'Did not find feedback ' . $feedbackType . ' in response: ' . $this->currentResponseContents);
    }
}
