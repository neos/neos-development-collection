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
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\Exception;
use Neos\Neos\Service\TransliterationService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

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
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

    /**
     * Sets the best possible uriPathSegment for the given Node.
     * Will use an already set uriPathSegment or alternatively the node name as base,
     * then checks if the uriPathSegment already exists on the same level and appends a counter until a unique path segment was found.
     *
     * @param NodeInterface $node
     * @return void
     */
    public function setUniqueUriPathSegment(NodeInterface $node)
    {
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $parentNode = $node->getParent();
            $documentNodeType = $this->nodeTypeManager->getNodeType('Neos.Neos:Document');
            $context = $node->getContext();
            $possibleUriPathSegment = $initialUriPathSegment = !$node->hasProperty('uriPathSegment') ? $node->getName() : $node->getProperty('uriPathSegment');
            $nodeExists = function ($possibleUriPathSegment) use ($documentNodeType, $context, $parentNode, $node) {
                $nodes = $this->nodeSearchService->findByProperties(['uriPathSegment' => $possibleUriPathSegment], [$documentNodeType], $context, $parentNode);
                $filteredNodes = array_filter($nodes, function ($currentNode) use ($node, $parentNode) {
                    // Make sure nodes are true siblings only (and not ancestors) and that they are not equal to each other
                    return ($currentNode->getParent()->getIdentifier() === $parentNode->getIdentifier()) && ($currentNode->getIdentifier() !== $node->getIdentifier());
                });
                return count($filteredNodes) > 0;
            };
            $i = 1;
            while ($nodeExists($possibleUriPathSegment)) {
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
