<?php

namespace Neos\Neos\Tests\Functional\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\Context as ContentContext;
use Neos\Flow\Http;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Http\DetectContentSubgraphComponent;

/**
 * Test case for the BackendUriDimensionPresetDetector
 */
class DetectContentSubgraphComponentTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $dimensionPresets = [
        'market' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN,
            ],
            'defaultPreset' => 'EU',
            'default' => 'EU',
            'presets' => [
                'WORLD' => [
                    'values' => ['WORLD'],
                    'resolutionValue' => 'com'
                ],
                'GB' => [
                    'values' => ['GB', 'WORLD'],
                    'resolutionValue' => 'co.uk'
                ],
                'DE' => [
                    'values' => ['DE', 'WORLD'],
                    'resolutionValue' => 'de'
                ]
            ]
        ],
        'seller' => [
            'defaultPreset' => 'default',
            'default' => 'default',
            'presets' => [
                'default' => [
                    'values' => ['default'],
                    'resolutionValue' => 'default'
                ],
                'sellerA' => [
                    'values' => ['sellerA', 'default'],
                    'resolutionValue' => 'sellerA'
                ]
            ]
        ],
        'channel' => [
            'defaultPreset' => 'default',
            'default' => 'default',
            'presets' => [
                'default' => [
                    'values' => ['default'],
                    'resolutionValue' => 'default'
                ],
                'channelA' => [
                    'values' => ['channelA', 'default'],
                    'resolutionValue' => 'channelA'
                ]
            ]
        ],
        'language' => [
            'resolution' => [
                'mode' => ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN,
                'options' => [
                    'allowEmptyValue' => true
                ]
            ],
            'default' => 'en',
            'defaultPreset' => 'en',
            'presets' => [
                'en' => [
                    'values' => ['en'],
                    'resolutionValue' => ''
                ],
                'de' => [
                    'values' => ['de'],
                    'resolutionValue' => 'de'
                ]
            ]
        ]
    ];


    /**
     * @test
     */
    public function handleAddsCorrectSubgraphIdentityToComponentContextWithAllDimensionValuesGivenLiveWorkspaceAndDefaultDelimiter()
    {
        $uri = new Http\Uri('https://de.domain.com/sellerA_channelA/home.html');
        $request = Http\Request::create($uri);
        $componentContext = new Http\Component\ComponentContext($request, new Http\Response());

        $detectSubgraphComponent = new DetectContentSubgraphComponent();
        $mockDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDimensionPresetSource->expects($this->any())
            ->method('getAllPresets')
            ->will($this->returnValue($this->dimensionPresets));
        $this->inject($detectSubgraphComponent, 'dimensionPresetSource', $mockDimensionPresetSource);

        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration($this->dimensionPresets);

        $detectSubgraphComponent->handle($componentContext);
        /** @var RouteParameters $routeParameters */
        $routeParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');

        $this->assertSame('live', $routeParameters->getValue('workspaceName'));
        $detectedDimensions = json_decode($routeParameters->getValue('dimensionValues'), true);
        $this->assertSame(['de'], $detectedDimensions['language']);
        $this->assertSame(['WORLD'], $detectedDimensions['market']);
        $this->assertSame(['sellerA', 'default'], $detectedDimensions['seller']);
        $this->assertSame(['channelA', 'default'], $detectedDimensions['channel']);
    }

    /**
     * @test
     */
    public function handleAddsCorrectSubgraphIdentityToComponentContextWithAllDimensionValuesGivenLiveWorkspaceAndModifiedDelimiter()
    {
        $uri = new Http\Uri('https://de.domain.com/sellerA-channelA/home.html');
        $request = Http\Request::create($uri);
        $componentContext = new Http\Component\ComponentContext($request, new Http\Response());

        $detectSubgraphComponent = new DetectContentSubgraphComponent();
        $mockDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDimensionPresetSource->expects($this->any())
            ->method('getAllPresets')
            ->will($this->returnValue($this->dimensionPresets));
        $this->inject($detectSubgraphComponent, 'dimensionPresetSource', $mockDimensionPresetSource);

        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration($this->dimensionPresets);

        $this->inject($detectSubgraphComponent, 'uriPathSegmentDelimiter', '-');

        $detectSubgraphComponent->handle($componentContext);
        /** @var RouteParameters $routeParameters */
        $routeParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');

        $this->assertSame('live', $routeParameters->getValue('workspaceName'));
        $detectedDimensions = json_decode($routeParameters->getValue('dimensionValues'), true);
        $this->assertSame(['de'], $detectedDimensions['language']);
        $this->assertSame(['WORLD'], $detectedDimensions['market']);
        $this->assertSame(['sellerA', 'default'], $detectedDimensions['seller']);
        $this->assertSame(['channelA', 'default'], $detectedDimensions['channel']);
    }

    /**
     * @test
     */
    public function handleAddsCorrectSubgraphIdentityToComponentContextWithMinimalDimensionValuesGivenLiveWorkspaceAndModifiedDelimiter()
    {
        $uri = new Http\Uri('https://domain.com/home.html');
        $request = Http\Request::create($uri);
        $componentContext = new Http\Component\ComponentContext($request, new Http\Response());

        $detectSubgraphComponent = new DetectContentSubgraphComponent();
        $mockDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDimensionPresetSource->expects($this->any())
            ->method('getAllPresets')
            ->will($this->returnValue($this->dimensionPresets));
        $this->inject($detectSubgraphComponent, 'dimensionPresetSource', $mockDimensionPresetSource);

        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration($this->dimensionPresets);

        $detectSubgraphComponent->handle($componentContext);
        /** @var RouteParameters $routeParameters */
        $routeParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');

        $this->assertSame('live', $routeParameters->getValue('workspaceName'));
        $detectedDimensions = json_decode($routeParameters->getValue('dimensionValues'), true);
        $this->assertSame(['en'], $detectedDimensions['language']);
        $this->assertSame(['WORLD'], $detectedDimensions['market']);
        $this->assertSame(['default'], $detectedDimensions['seller']);
        $this->assertSame(['default'], $detectedDimensions['channel']);
    }


    /**
     * @test
     */
    public function handleAddsCorrectSubgraphIdentityToComponentContextWithDimensionValuesGivenButOverriddenViaContextPath()
    {
        $uri = new Http\Uri('https://de.domain.com/sellerA_channelA/home@user-me;language=en&market=GB,WORLD&seller=default&channel=default.html');
        $request = Http\Request::create($uri);
        $componentContext = new Http\Component\ComponentContext($request, new Http\Response());

        $detectSubgraphComponent = new DetectContentSubgraphComponent();
        $mockDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDimensionPresetSource->expects($this->any())
            ->method('getAllPresets')
            ->will($this->returnValue($this->dimensionPresets));
        $this->inject($detectSubgraphComponent, 'dimensionPresetSource', $mockDimensionPresetSource);

        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration($this->dimensionPresets);

        $detectSubgraphComponent->handle($componentContext);
        /** @var RouteParameters $routeParameters */
        $routeParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');

        $this->assertSame('user-me', $routeParameters->getValue('workspaceName'));
        $detectedDimensions = json_decode($routeParameters->getValue('dimensionValues'), true);
        $this->assertSame(['en'], $detectedDimensions['language']);
        $this->assertSame(['GB', 'WORLD'], $detectedDimensions['market']);
        $this->assertSame(['default'], $detectedDimensions['seller']);
        $this->assertSame(['default'], $detectedDimensions['channel']);
    }
}
