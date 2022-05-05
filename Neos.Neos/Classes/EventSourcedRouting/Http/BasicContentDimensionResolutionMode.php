<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\Http;

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
 * The basic content dimension resolution mode value object
 */
final class BasicContentDimensionResolutionMode implements \JsonSerializable
{
    const RESOLUTION_MODE_HOSTPREFIX = 'hostPrefix';
    const RESOLUTION_MODE_HOSTSUFFIX = 'hostSuffix';
    const RESOLUTION_MODE_URIPATHSEGMENT = 'uriPathSegment';
    const RESOLUTION_MODE_NULL = 'null';

    /**
     * @var string
     */
    protected $mode;

    /**
     * @param string $mode
     */
    public function __construct(string $mode)
    {
        if ($mode !== self::RESOLUTION_MODE_HOSTPREFIX
            && $mode !== self::RESOLUTION_MODE_HOSTSUFFIX
            && $mode !== self::RESOLUTION_MODE_URIPATHSEGMENT
            && $mode !== self::RESOLUTION_MODE_NULL
        ) {
            throw new \InvalidArgumentException(
                'Invalid basic content dimension resolution mode "'
                    . $mode . '", must be one of the defined constants.',
                1510778102
            );
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
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->mode;
    }
}
