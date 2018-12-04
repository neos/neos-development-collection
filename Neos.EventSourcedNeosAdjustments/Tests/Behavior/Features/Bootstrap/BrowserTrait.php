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

    abstract function getObjectManager(): \Neos\Flow\ObjectManagement\ObjectManagerInterface;

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
     * @When /^I visit "([^"]*)"$/
     */
    public function iVisit($uriPath)
    {
        $this->currentResponse = $this->browser->request(new \Neos\Flow\Http\Uri('http://localhost' . $uriPath));
    }

    /**
     * @Then /^the content of the page contains "([^"]*)"$/
     */
    public function theContentOfThePageContains($expectedString)
    {
        Assert::assertContains($expectedString, $this->currentResponse->getBody()->getContents());
    }


}
