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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
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
    protected LocaleUtility $localeUtility;

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

        $locale = $this->localeUtility->createForDimensionSpacePoint(
            $node?->originDimensionSpacePoint->toDimensionSpacePoint() ?? DimensionSpacePoint::fromArray([]),
            $node->subgraphIdentity->contentRepositoryId
        );

        $transliteratedText = $this->transliterationService->transliterate($textForNode, $locale?->getLanguage());
        return Transliterator::urlize($transliteratedText);

    }
}
