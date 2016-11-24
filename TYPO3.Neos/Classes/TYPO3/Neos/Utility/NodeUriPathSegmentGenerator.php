<?php
namespace TYPO3\Neos\Utility;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Transliterator\Transliterator;
use TYPO3\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use TYPO3\Neos\Exception;
use TYPO3\Neos\Service\TransliterationService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
     * Sets the best possible uriPathSegment for the given Node.
     * Will use an already set uriPathSegment or alternatively the node name as base,
     * then checks if the uriPathSegment already exists on the same level and appends a counter until a unique path segment was found.
     *
     * @param NodeInterface $node
     * @return void
     */
    public static function setUniqueUriPathSegment(NodeInterface $node)
    {
        if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
            $q = new FlowQuery(array($node));
            $q = $q->context(array('invisibleContentShown' => true, 'removedContentShown' => true, 'inaccessibleContentShown' => true));

            $possibleUriPathSegment = $initialUriPathSegment = !$node->hasProperty('uriPathSegment') ? $node->getName() : $node->getProperty('uriPathSegment');
            $i = 1;
            while ($q->siblings('[instanceof TYPO3.Neos:Document][uriPathSegment="' . $possibleUriPathSegment . '"]')->count() > 0) {
                $possibleUriPathSegment = $initialUriPathSegment . '-' . $i++;
            }
            $node->setProperty('uriPathSegment', $possibleUriPathSegment);
        }
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
            if (array_key_exists('language', $dimensions) && $dimensions['language'] !== array()) {
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
