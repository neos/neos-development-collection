<?php
namespace Neos\Neos\Tests\Unit\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;
use Neos\Neos\Http\ContentDimensionLinking\UriPathSegmentContentDimensionValueUriProcessor;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the UriPathSegmentContentDimensionValueUriProcessor
 */
class UriPathSegmentContentDimensionValueUriProcessorTest extends UnitTestCase
{
    /**
     * @var Dimension\ContentDimension
     */
    protected $market;

    /**
     * @var Dimension\ContentDimension
     */
    protected $language;

    public function setUp()
    {
        parent::setUp();
        $defaultMarket = new Dimension\ContentDimensionValue('default', null, [], ['resolution' => ['value' => 'defaultMarket']]);
        $defaultLanguage = new Dimension\ContentDimensionValue('default', null, [], ['resolution' => ['value' => 'defaultLanguage']]);

        $this->market = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('market'),
            [
                (string)$defaultMarket => $defaultMarket
            ],
            $defaultMarket,
            [],
            [
                'resolution' => [
                    'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                    'options' => [
                        'offset' => 1
                    ]
                ]
            ]
        );

        $this->language = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('language'),
            [
                (string)$defaultLanguage => $defaultLanguage
            ],
            $defaultLanguage,
            [],
            [
                'resolution' => [
                    'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT,
                    'options' => [
                        'offset' => 0
                    ]
                ]
            ]
        );
    }


    /**
     * @test
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsFirstPathPrefix()
    {
        $uriProcessor = new UriPathSegmentContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $options = $this->language->getConfigurationValue('resolution.options');
        $options['delimiter'] = '-';

        $processedUriConstraints = $uriProcessor->processUriConstraints(
            $uriConstraints,
            $this->language,
            $this->language->getValue('default'),
            $options
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            'defaultLanguage',
            $constraints['pathPrefix']
        );
    }

    /**
     * @test
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsSecondPathPrefixWithGivenDelimiter()
    {
        $uriProcessor = new UriPathSegmentContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $options = $this->market->getConfigurationValue('resolution.options');
        $options['delimiter'] = '-';

        $processedUriConstraints = $uriProcessor->processUriConstraints(
            $uriConstraints,
            $this->market,
            $this->market->getValue('default'),
            $options
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            '-defaultMarket',
            $constraints['pathPrefix']
        );
    }
}
