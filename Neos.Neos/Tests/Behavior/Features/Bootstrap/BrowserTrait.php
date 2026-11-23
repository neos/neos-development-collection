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

use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;

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
}
