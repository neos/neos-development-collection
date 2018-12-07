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
        $this->browser->setRequestEngine(new \Neos\Flow\Http\Client\InternalRequestEngine());
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
            var_dump($uriPath);
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
}
