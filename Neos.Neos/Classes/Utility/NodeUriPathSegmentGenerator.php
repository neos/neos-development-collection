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
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
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

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    /**
     * Generates a URI path segment for a given node taking its language dimension value into account
     *
     * @param ?Node $node Optional node to determine language dimension value from
     * @param ?string $text Optional text
     */
    public function generateUriPathSegment(?Node $node = null, ?string $text = null): string
    {
        $language = null;
        if ($node) {
            $text = $text ?: $this->nodeLabelGenerator->getLabel($node) ?: ($node->name?->value ?? '');
            $languageDimensionValue = $node->originDimensionSpacePoint->coordinates['language'] ?? null;
            if (!is_null($languageDimensionValue)) {
                $locale = new Locale($languageDimensionValue);
                $language = $locale->getLanguage();
            }
        } elseif (is_null($text) || empty($text)) {
            throw new Exception('Given text was empty.', 1457591815);
        }
        $text = $this->transliterationService->transliterate($text, $language);

        return Transliterator::urlize($text);
    }
}
