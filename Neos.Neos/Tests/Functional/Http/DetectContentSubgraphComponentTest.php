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
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Flow\Http;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Http\DetectContentSubgraphComponent;

/**
 * Test cases for the DetectContentSubgraphComponent
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
            'defaultPreset' => 'WORLD',
            'default' => 'WORLD',
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

    public function uriProvider(): array
    {
        return [
            ['https://de.domain.com/sellerA_channelA', '_', 'live', ['WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['de']],
            ['https://de.domain.com/sellerA_channelA/', '_', 'live', ['WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['de']],
            ['https://de.domain.com/sellerA_channelA/home.html', '_', 'live', ['WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['de']],
            ['https://de.domain.com/sellerA-channelA/home.html', '-', 'live', ['WORLD'], ['sellerA', 'default'], ['channelA', 'default'], ['de']],
            ['https://domain.com/home.html', '-', 'live', ['WORLD'], ['default'], ['default'], ['en']],
            ['https://de.domain.co.uk/sellerA_channelA/home@user-me;language=en&market=DE,WORLD&seller=default&channel=default.html', '-', 'user-me', ['DE', 'WORLD'], ['default'], ['default'], ['en']],
        ];
    }

    /**
     * @test
     * @dataProvider uriProvider
     * @param string $rawUri
     * @param string $delimiter
     * @param string $expectedWorkspaceName
     * @param array $expectedMarketValues
     * @param array $expectedSellerValues
     * @param array $expectedChannelValues
     * @param array $expectedLanguageValues
     */
    public function handleAddsCorrectSubgraphIdentityToComponentContext(
        string $rawUri,
        string $delimiter,
        string $expectedWorkspaceName,
        array $expectedMarketValues,
        array $expectedSellerValues,
        array $expectedChannelValues,
        array $expectedLanguageValues
    ) {
        $request = Http\Request::create(new Http\Uri($rawUri));
        $componentContext = new Http\Component\ComponentContext($request, new Http\Response());

        $dimensionPresetSource = $this->objectManager->get(ContentDimensionPresetSourceInterface::class);
        $dimensionPresetSource->setConfiguration($this->dimensionPresets);
        $detectSubgraphComponent = new DetectContentSubgraphComponent();

        $this->inject($detectSubgraphComponent, 'uriPathSegmentDelimiter', $delimiter);

        $detectSubgraphComponent->handle($componentContext);
        /** @var RouteParameters $routeParameters */
        $routeParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');

        $this->assertSame($expectedWorkspaceName, $routeParameters->getValue('workspaceName'));
        $detectedDimensions = json_decode($routeParameters->getValue('dimensionValues'), true);
        $this->assertSame($expectedMarketValues, $detectedDimensions['market']);
        $this->assertSame($expectedSellerValues, $detectedDimensions['seller']);
        $this->assertSame($expectedChannelValues, $detectedDimensions['channel']);
        $this->assertSame($expectedLanguageValues, $detectedDimensions['language']);
    }
}
