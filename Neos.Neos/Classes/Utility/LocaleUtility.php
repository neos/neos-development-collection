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

use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;

class LocaleUtility
{
    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepositoryRegistry", "contentRepositories")
     */
    protected $crSettings;

    public function createForDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint, ContentRepositoryId $contentRepositoryId): ?Locale
    {
        $localeDimensionKey = $this->crSettings[$contentRepositoryId->value]['localeDimensionKey'] ?? 'language';
        $languageDimensionValue = $dimensionSpacePoint->getCoordinate(new ContentDimensionId($localeDimensionKey));
        if ($languageDimensionValue !== null) {
            try {
                return new Locale($languageDimensionValue);
            } catch (InvalidLocaleIdentifierException $e) {
                // since we dont enforce a specific locale format in the dsp we will silently ignore this
            }
        }
        return null;
    }
}
