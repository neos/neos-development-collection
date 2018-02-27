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
use Neos\Neos\Http\ContentDimensionLinking\HostSuffixContentDimensionValueUriProcessor;
use Neos\Utility\ObjectAccess;

/**
 * Test cases for the HostSuffixContentDimensionValueUriProcessor
 */
class HostSuffixContentDimensionValueUriProcessorTest extends UnitTestCase
{
    /**
     * @var Dimension\ContentDimension
     */
    protected $contentDimension;

    public function setUp()
    {
        parent::setUp();
        $english = new Dimension\ContentDimensionValue('en', null, [], ['resolution' => ['value' => '.com']]);
        $french = new Dimension\ContentDimensionValue('fr', null, [], ['resolution' => ['value' => '.fr']]);

        $this->contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('market'),
            [
                (string) $english => $english,
                (string) $french => $french
            ],
            $english,
            [],
            [
                'resolution' => [
                    'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX,
                ],
                'allowEmptyValue' => false
            ]
        );
    }

    /**
     * @test
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsHostSuffixWithReplacementsIfGiven()
    {
        $linkProcessor = new HostSuffixContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            $this->contentDimension,
            $this->contentDimension->getValue('fr'),
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'suffix' => '.fr',
                'replaceSuffixes' => ['.com', '.fr']
            ],
            $constraints['hostSuffix']
        );
    }
}
