<?php
namespace Neos\Neos\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension resolution mode value object
 */
final class ContentDimensionResolutionMode implements \JsonSerializable
{
    const RESOLUTION_MODE_SUBDOMAIN = 'subdomain';
    const RESOLUTION_MODE_TOPLEVELDOMAIN = 'topLevelDomain';
    const RESOLUTION_MODE_URIPATHSEGMENT = 'uriPathSegment';

    /**
     * @var string
     */
    protected $mode;

    /**
     * @param string $mode
     */
    public function __construct(string $mode)
    {
        if ($mode !== self::RESOLUTION_MODE_SUBDOMAIN
            && $mode !== self::RESOLUTION_MODE_TOPLEVELDOMAIN
            && $mode !== self::RESOLUTION_MODE_URIPATHSEGMENT
        ) {
            throw new \InvalidArgumentException('Invalid content dimension resolution mode "' . $mode . '", must be one of the defined constants.', 1510778102);
        }

        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->mode;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode
        ];
    }
}
