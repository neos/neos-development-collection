<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\NodeCreationHandler;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Service\TransliterationService;

/**
 * Node creation handler that
 *
 * - sets the "title" property according to the incoming title from a creation dialog
 * - sets the "uriPathSegment" property according to the specified title or node name
 *
 * Note: This is not actually a Command Handler in the sense of CQRS but rather some kind of
 *       "command enricher"
 */
class DocumentTitleNodeCreationHandler implements NodeCreationHandlerInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var TransliterationService
     */
    protected $transliterationService;

    public function handle(CreateNodeAggregateWithNode $command, array $data): CreateNodeAggregateWithNode
    {
        if (!$this->nodeTypeManager->getNodeType($command->getNodeTypeName()->getValue())
            ->isOfType('Neos.Neos:Document')) {
            return $command;
        }
        $propertyValues = $command->getInitialPropertyValues();
        if (isset($data['title'])) {
            $propertyValues = $propertyValues->withValue('title', $data['title']);
        }

        // if specified, the uriPathSegment equals the title
        $uriPathSegment = $data['title'];

        // otherwise, we fall back to the node name
        if ($uriPathSegment === null && $command->getNodeName() !== null) {
            $uriPathSegment = (string)$command->getNodeName();
        }

        // if not empty, we transliterate the uriPathSegment according to the language of the new node
        if ($uriPathSegment !== null && $uriPathSegment !== '') {
            $uriPathSegment = $this->transliterateText($command->getOriginDimensionSpacePoint(), $uriPathSegment);
        } else {
            // alternatively we set it to a random string
            $uriPathSegment = uniqid('', true);
        }
        $uriPathSegment = Transliterator::urlize($uriPathSegment);
        $propertyValues = $propertyValues->withValue('uriPathSegment', $uriPathSegment);

        return $command->withInitialPropertyValues($propertyValues);
    }

    private function transliterateText(DimensionSpacePoint $dimensionSpacePoint, string $text): string
    {
        $languageDimensionValue = $dimensionSpacePoint->getCoordinate(new ContentDimensionIdentifier('language'));
        if ($languageDimensionValue !== null) {
            try {
                $language = (new Locale($languageDimensionValue))->getLanguage();
            } catch (InvalidLocaleIdentifierException $e) {
                // we don't need to do anything here; we'll just transliterate the text.
            }
        }
        return $this->transliterationService->transliterate($text, $language ?? null);
    }
}
