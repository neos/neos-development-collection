<?php

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
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
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
    abstract public function getCurrentNodeAddress(): \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress;

    /**
     * @var \Neos\Flow\Http\Client\Browser
     */
    protected $browser;

    protected function setupBrowserTrait()
    {
        $this->browser = new \Neos\Flow\Http\Client\Browser();
        $this->browser->setRequestEngine(new \Neos\EventSourcedNeosAdjustments\Testing\CustomizedInternalRequestEngine());
        $bootstrap = $this->getObjectManager()->get(\Neos\Flow\Core\Bootstrap::class);

        $bootstrap->setActiveRequestHandler(new \Neos\Flow\Tests\FunctionalTestRequestHandler($bootstrap));
        $requestHandler = $bootstrap->getActiveRequestHandler();
        $request = Request::create(new \Neos\Flow\Http\Uri('http://localhost/flow/test'));
        $componentContext = new ComponentContext($request, new \Neos\Flow\Http\Response());
        $requestHandler->setComponentContext($componentContext);
    }

    /**
     * @var \Neos\Flow\Http\Response
     */
    protected $currentResponse;

    /**
     * @var Request
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
        $this->currentResponse = $this->browser->request(new \Neos\Flow\Http\Uri('http://localhost' . $uriPath));
        $this->currentRequest = $this->browser->getLastRequest();
    }

    /**
     * @Then /^the content of the page contains "([^"]*)"$/
     */
    public function theContentOfThePageContains($expectedString)
    {
        Assert::assertContains($expectedString, $this->currentResponse->getBody()->getContents());
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
        $this->browser->request(new \Neos\Flow\Http\Uri('http://localhost/neos/login'), 'POST', [
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
            /* @var $nodeAddress \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress */
            $nodeAddressString = str_replace($alias, $nodeAddress->serializeForUri(), $nodeAddressString);
        }

        return $nodeAddressString;
    }

    /**
     * @When /^I send the following changes:$/
     */
    public function iSendTheFollowingChanges(TableNode $changeDefinition)
    {
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
        $this->currentResponse = $this->browser->request(new \Neos\Flow\Http\Uri('http://localhost/neos/ui-services/change'), 'POST', ['changes' => $changes], [], $server);
        $this->currentRequest = $this->browser->getLastRequest();
        Assert::assertEquals(200, $this->currentResponse->getStatusCode(), 'Status code wrong. Full response was: ' . $this->currentResponse->getBody()->getContents());
    }

    /**
     * @Then /^the feedback contains "([^"]*)"$/
     */
    public function theFeedbackContains($feedbackType)
    {
        $bodyContents = $this->currentResponse->getBody()->getContents();
        $body = json_decode($bodyContents, true);
        foreach ($body['feedbacks'] as $feedback) {
            if ($feedback['type'] === $feedbackType) {
                Assert::assertTrue(true, 'Feedback found');
                return;
            }
        }
        Assert::assertTrue(false, 'Did not find feedback ' . $feedbackType . ' in response: ' . $bodyContents);
    }
}
