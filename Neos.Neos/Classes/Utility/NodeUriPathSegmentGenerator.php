<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Behat\Transliterator\Transliterator;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Exception;
use Neos\Neos\Service\TransliterationService;

/**
 * Utility to generate a valid, non-conflicting uriPathSegment for nodes.
 */
#[Flow\Scope('singleton')]
class NodeUriPathSegmentGenerator
{
    #[Flow\Inject]
    protected TransliterationService $transliterationService;

    /**
     * Generates a URI path segment for a given node taking its language dimension value into account
     *
     * @param ?Node $node Optional node to determine language dimension value from
     * @param ?string $text Optional text
     */
    public function generateUriPathSegment(?Node $node = null, ?string $text = null): string
    {
        if ($node === null && empty($text)) {
            throw new Exception('Given text was empty.', 1457591815);
        }

        $textForNode = $text ?: $node->getLabel() ?: $node->nodeName?->value ?? '';

        return $this->generateUriPathSegmentFromTextForDimension(
            $textForNode,
            $node?->originDimensionSpacePoint->toDimensionSpacePoint() ?? DimensionSpacePoint::fromArray([])
        );
    }

    /**
     * Generates a URI path segment for a given text taking the language dimension value into account
     *
     * @param string $text
     * @param DimensionSpacePoint $dimensionSpacePoint to determine language dimension value from
     */
    public function generateUriPathSegmentFromTextForDimension(string $text, DimensionSpacePoint $dimensionSpacePoint): string
    {
        $languageDimensionValue = $dimensionSpacePoint->getCoordinate(new ContentDimensionId('language'));
        $language = null;
        if ($languageDimensionValue !== null) {
            try {
                $language = (new Locale($languageDimensionValue))->getLanguage();
            } catch (InvalidLocaleIdentifierException $e) {
                // we don't need to do anything here; we'll just transliterate the text.
            }
        }
        $transliteratedText = $this->transliterationService->transliterate($text, $language);
        return Transliterator::urlize($transliteratedText);
    }
}
