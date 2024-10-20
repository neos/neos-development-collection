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

use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\Psr7\Message;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Neos\Domain\Service\FusionService;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * @internal only for behat tests within the Neos.Neos package
 */
trait DispatcherTrait
{
    use CRTestSuiteRuntimeVariables;

    private ResponseInterface|null $response = null;

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
    public function setupDispatcherTest(): void
    {
        $this->getObject(ContentCache::class)->flush();
        $this->response = null;
    }

    /**
     * @When the sites Fusion code is:
     */
    public function iHaveTheFollowingFusionCodeForTheSite(PyStringNode $fusionCode)
    {
        $this->getObject(
            FusionService::class
        )->unsafeSetAdditionalFusionSourceCodeToThisSingleton(
            $fusionCode->getRaw()
        );
        // $fakeFusionService = new class ($original) extends \Neos\Neos\Domain\Service\FusionService
        // {
        //     public function __construct(
        //         private \Neos\Neos\Domain\Service\FusionService $original,
        //         private \Neos\Fusion\Core\FusionSourceCode $additionalFusion
        //     ) {
        //     }
        //     public function createFusionConfigurationFromSite(\Neos\Neos\Domain\Model\Site $site): \Neos\Fusion\Core\FusionConfiguration
        //     {
        //         $this->original->createFusionConfigurationFromSite($site)-> ... doest work
        //     }
        // };
    }

    /**
     * @When I declare the following controller :fullyQualifiedClassName:
     */
    public function iDeclareTheFollowingController(string $fullyQualifiedClassName, PyStringNode $expectedResult): void
    {
        eval($expectedResult->getRaw());

        $controllerInstance = new ('\\' . $fullyQualifiedClassName)();

        if ($controllerInstance instanceof \Neos\Flow\Mvc\Controller\ActionController) {
            // inject all the necessary properties of an action controller, as extended classes dont call $this->Flow_Proxy_injectProperties();
            \Neos\Utility\ObjectAccess::setProperty($controllerInstance, 'validatorResolver', $this->getObject(\Neos\Flow\Validation\ValidatorResolver::class), true);
            \Neos\Utility\ObjectAccess::setProperty($controllerInstance, 'mvcPropertyMappingConfigurationService', $this->getObject(\Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class), true);
            \Neos\Utility\ObjectAccess::setProperty($controllerInstance, 'viewConfigurationManager', $this->getObject(\Neos\Flow\Mvc\ViewConfigurationManager::class), true);
            \Neos\Utility\ObjectAccess::setProperty($controllerInstance, 'objectManager', $this->getObject(\Neos\Flow\ObjectManagement\ObjectManager::class), true);
        }


        $objectManager = $this->getObject(\Neos\Flow\ObjectManagement\ObjectManager::class);
        $objects = \Neos\Utility\ObjectAccess::getProperty($objectManager, 'objects', true);
        $objects[get_class($controllerInstance)]['i'] = $controllerInstance;
        $objects[get_class($controllerInstance)]['l'] = strtolower(get_class($controllerInstance));
        $objectManager->setObjects($objects);
    }

    /**
     * @When I dispatch the following request :requestUri
     */
    public function iDispatchTheFollowingRequest(string $requestUri)
    {
        $httpRequest = $this->getObject(ServerRequestFactoryInterface::class)->createServerRequest('GET', $requestUri);

        $this->response = $this->getObject(\Neos\Flow\Http\Middleware\MiddlewaresChain::class)->handle(
            $httpRequest
        );
    }

    /**
     * @Then I expect the following response:
     */
    public function iExpectTheFollowingResponse(PyStringNode $expectedResult): void
    {
        Assert::assertNotNull($this->response);
        Assert::assertEquals($expectedResult->getRaw(), str_replace("\r\n", "\n", Message::toString($this->response->withoutHeader('Content-Length'))));
    }
}
