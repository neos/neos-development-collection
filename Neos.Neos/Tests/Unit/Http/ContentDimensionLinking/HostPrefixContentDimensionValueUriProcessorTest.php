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
use Neos\Neos\Http\ContentDimensionLinking\HostPrefixContentDimensionValueUriProcessor;
use Neos\Utility\ObjectAccess;

/**
 * Test case for the HostPrefixContentDimensionValueUriProcessor
 */
class HostPrefixContentDimensionValueUriProcessorTest extends UnitTestCase
{
    /**
     * @var Dimension\ContentDimension
     */
    protected $contentDimension;

    public function setUp()
    {
        parent::setUp();
        $english = new Dimension\ContentDimensionValue('en', null, [], ['resolution' => ['value' => '']]);
        $german = new Dimension\ContentDimensionValue('de', null, [], ['resolution' => ['value' => 'de.']]);
        $french = new Dimension\ContentDimensionValue('fr', null, [], ['resolution' => ['value' => 'fr.']]);

        $this->contentDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('language'),
            [
                (string) $english => $english,
                (string) $german => $german,
                (string) $french => $french
            ],
            $english,
            [],
            [
                'resolution' => [
                    'mode' => BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX,
                ],
                'allowEmptyValue' => true
            ]
        );
    }

    /**
     * @test
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsHostPrefixWithReplacementsIfGiven()
    {
        $uriProcessor = new HostPrefixContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $uriProcessor->processUriConstraints(
            $uriConstraints,
            $this->contentDimension,
            $this->contentDimension->getValue('fr'),
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'prefix' => 'fr.',
                'replacePrefixes' => ['de.', 'fr.']
            ],
            $constraints['hostPrefix']
        );
    }

    /**
     * @test
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function processUriConstraintsAddsEmptyHostPrefixWithReplacementsIfGiven()
    {
        $linkProcessor = new HostPrefixContentDimensionValueUriProcessor();
        $uriConstraints = UriConstraints::create();

        $processedUriConstraints = $linkProcessor->processUriConstraints(
            $uriConstraints,
            $this->contentDimension,
            $this->contentDimension->getValue('en'),
            []
        );
        $constraints = ObjectAccess::getProperty($processedUriConstraints, 'constraints', true);

        $this->assertSame(
            [
                'prefix' => '',
                'replacePrefixes' => ['de.', 'fr.']
            ],
            $constraints['hostPrefix']
        );
    }
}
