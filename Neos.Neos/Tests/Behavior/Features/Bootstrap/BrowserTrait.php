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
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait BrowserTrait
{

    /**
     * @return \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    abstract protected function getObjectManager();
    abstract protected function getCurrentNodeAddress(string $alias = null): \Neos\ContentRepository\Core\SharedModel\NodeAddress;

    /**
     * @var \Neos\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * @BeforeScenario
     */
    public function setupBrowserForEveryScenario()
    {
        // we reset the security context at the beginning of every scenario; such that we start with a clean session at
        // every scenario and SHARE the session throughout the scenario!
        $this->getObjectManager()->get(\Neos\Flow\Security\Context::class)->clearContext();

        $this->browser = new \Neos\Flow\Http\Client\Browser();
        $this->browser->setRequestEngine(new \Neos\Neos\Testing\CustomizedInternalRequestEngine());
        $bootstrap = $this->getObjectManager()->get(\Neos\Flow\Core\Bootstrap::class);

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
            /* @var $nodeAddress \Neos\ContentRepository\Core\SharedModel\NodeAddress */
            $nodeAddressString = str_replace($alias, $nodeAddress->serializeForUri(), $nodeAddressString);
        }

        return $nodeAddressString;
    }

    /**
     * @When /^I send the following changes:$/
     */
    public function iSendTheFollowingChanges(TableNode $changeDefinition)
    {
        $this->getObjectManager()->get(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

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
            'HTTP_X_FLOW_CSRFTOKEN' => $this->getObjectManager()->get(\Neos\Flow\Security\Context::class)->getCsrfProtectionToken(),
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
        $this->getObjectManager()->get(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        $nodeContextPaths = [];
        foreach ($nodesToPublish->getHash() as $singleChange) {
            $nodeContextPaths[] = $this->replacePlaceholders($singleChange['Subject Node Address']);
        }

        $server = [
            'HTTP_X_FLOW_CSRFTOKEN' => $this->getObjectManager()->get(\Neos\Flow\Security\Context::class)->getCsrfProtectionToken(),
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
