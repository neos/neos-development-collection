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

        // doesnt work as the packages base path cannot change ... we would need to create an actual package in /Packages as rescanPackages() will be invoked
        // vfsStream::setup('packages');
        // $this->getObject(\Neos\Flow\Package\PackageManager::class)->createPackage('Vendor.Site', [], 'vfs://packages/');
        // file_put_contents('resource://Vendor.Site/Private/Fusion/Root.fusion', $fusionCode->getRaw());
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
     * @Then I expect the following response header:
     */
    public function iExpectTheFollowingResponseHeader(PyStringNode $expectedResult): void
    {
        Assert::assertNotNull($this->response);
        Assert::assertSame($expectedResult->getRaw(), $this->response->getBody()->getContents());
    }

    /**
     * @Then I expect the following response:
     */
    public function iExpectTheFollowingResponse(PyStringNode $expectedResult): void
    {
        Assert::assertNotNull($this->response);
        Assert::assertEquals($expectedResult->getRaw(), str_replace("\r\n", "\n", Message::toString($this->response)));
    }
}
