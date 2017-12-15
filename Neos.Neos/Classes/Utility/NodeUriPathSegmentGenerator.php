<?php
namespace Neos\Neos\Utility;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Transliterator\Transliterator;
use Doctrine\Common\Persistence\ObjectManager;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\Model\NodeUriPath;
use Neos\Neos\Exception;
use Neos\Neos\Service\TransliterationService;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Utility to generate a valid, non-conflicting uriPathSegment for nodes.
 *
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
     * @var ObjectManager
     * @Flow\Inject
     */
    protected $entityManager;

    /**
     * Sets the best possible uriPathSegment for the given Node.
     * Will use an already set uriPathSegment or alternatively the node name as base,
     * then checks if the uriPathSegment already exists on the same level and appends a counter until a unique path segment was found.
     *
     * @param NodeInterface $node
     * @return void
     */
    public static function setUniqueUriPathSegment(NodeInterface $node)
    {
        $node->setProperty('uriPathSegment', (new NodeUriPath($node))->find());
    }

    /**
     * Generates a URI path segment for a given node taking it's language dimension into account
     *
     * @param NodeInterface $node Optional node to determine language dimension
     * @param string $text Optional text
     * @return string
     */
    public function generateUriPathSegment(NodeInterface $node = null, $text = null)
    {
        if ($node) {
            $text = $text ?: $node->getLabel() ?: $node->getName();
            $dimensions = $node->getContext()->getDimensions();
            if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
                $locale = new Locale($dimensions['language'][0]);
                $language = $locale->getLanguage();
            }
        } elseif (strlen($text) === 0) {
            throw new Exception('Given text was empty.', 1457591815);
        }
        $text = $this->transliterationService->transliterate($text, isset($language) ? $language : null);
        return Transliterator::urlize($text);
    }
}
