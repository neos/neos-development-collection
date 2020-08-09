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
        if (!$this->nodeTypeManager->getNodeType($command->getNodeTypeName()->getValue())->isOfType('Neos.Neos:Document')) {
            return $command;
        }
        $propertyValues = $command->getInitialPropertyValues();
        if (isset($data['title'])) {
            $propertyValues = $propertyValues->withValue('title', $data['title']);
        }

        $uriPathSegment = $data['title'];
        if ($uriPathSegment === null && $command->getNodeName() !== null) {
            $uriPathSegment = (string)$command->getNodeName();
        }
        if ($uriPathSegment !== null && $uriPathSegment !== '') {
            $uriPathSegment = $this->transliterateText($command->getOriginDimensionSpacePoint(), $uriPathSegment);
        } else {
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
