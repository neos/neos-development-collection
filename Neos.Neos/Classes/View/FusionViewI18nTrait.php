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

namespace Neos\Neos\View;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
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
     * @throws InvalidLocaleIdentifierException
     */
    protected function setFallbackRuleFromDimension(DimensionSpacePoint $dimensionSpacePoint): void
    {
        $dimensions = $dimensionSpacePoint->coordinates;
        if (array_key_exists('language', $dimensions)) {
            $currentLocale = new Locale($dimensions['language']);
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            $this->i18nService->getConfiguration()->setFallbackRule([
                'strict' => $this->i18nService->getConfiguration()->getFallbackRule()['strict'],
                'order' => [$dimensions['language']]
            ]);
        }
    }
}
