<?php
declare(strict_types=1);

namespace Neos\Media\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\Asset;

final class AssetClass
{
    /**
     * Further possible supported classes will be "Document", "Audio" and "Video". Support for these
     * is not implemented yet and thus cannot be enabled by simply allowing the respective class.
     *
     * @const array
     */
    private const SUPPORTED_CLASSES = ['Image'];

    /**
     * @var string
     */
    private $assetClass;

    /**
     * @param string $assetClass
     */
    public function __construct(string $assetClass)
    {
        if (!in_array($assetClass, self::SUPPORTED_CLASSES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid asset class "%s". Supported classes are: %s', $assetClass, implode(', ', self::SUPPORTED_CLASSES)), 1552911048);
        }
        $this->assetClass = $assetClass;
    }

    /**
     * @return string
     */
    public function getFullyQualifiedClassName(): string
    {
        return substr(Asset::class, 0, strrpos(Asset::class, '\\') + 1) . $this->assetClass;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->assetClass;
    }
}
