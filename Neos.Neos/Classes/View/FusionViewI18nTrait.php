<?php
declare(strict_types=1);

namespace Neos\Neos\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;

trait FusionViewI18nTrait
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $i18nService;

    /**
     * If a content dimension named "language" exists, it is used to set the locale fallback
     * chain order for rendering based on that.
     *
     * This overrides the fallback order from Neos.Flow.i18n.fallbackRule.order - the strict
     * flag is kept from the settings!
     *
     * @param TraversableNodeInterface $currentSiteNode
     * @return void
     * @throws InvalidLocaleIdentifierException
     */
    protected function setFallbackRuleFromDimension(TraversableNodeInterface $currentSiteNode): void
    {
        $dimensions = $currentSiteNode->getContext()->getDimensions();
        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            $currentLocale = new Locale($dimensions['language'][0]);
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            array_shift($dimensions['language']);
            $this->i18nService->getConfiguration()->setFallbackRule([
                'strict' => $this->i18nService->getConfiguration()->getFallbackRule()['strict'],
                'order' => $dimensions['language']
            ]);
        }
    }
}
