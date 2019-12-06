<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Transliterator\Transliterator;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Service\TransliterationService;

/**
 * @Flow\Scope("singleton")
 */
class NodeUriPathSegmentGenerator
{
    /**
     * @Flow\Inject
     * @var TransliterationService
     */
    protected $transliterationService;

    /**
     * Generates a URI path segment for a given node taking it's language dimension into account
     *
     * @param TraversableNodeInterface $node Optional node to determine language dimension
     * @param string $text Optional text
     * @return string
     */
    public function generateUriPathSegment(?TraversableNodeInterface $node = null, ?string $text = ''): string
    {
        if ($node) {
            $text = $text === '' ? (string)($text ?: $node->getLabel() ?: $node->getNodeName()) : $text;
            $languageDimensionValue = $node->getDimensionSpacePoint()->getCoordinate(new ContentDimensionIdentifier('language'));
            if ($languageDimensionValue !== null) {
                try {
                    $locale = new Locale($languageDimensionValue);
                    $language = $locale->getLanguage();
                } catch (InvalidLocaleIdentifierException $e) {
                    // we don't need to do anything here; we'll just transliterate the text.
                }
            }
        }
        if ($text === '') {
            throw new \InvalidArgumentException('Given text was empty.', 1543916961);
        }
        $text = $this->transliterationService->transliterate($text, $language ?? null);
        return Transliterator::urlize($text);
    }
}
