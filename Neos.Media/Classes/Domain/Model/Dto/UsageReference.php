<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Dto;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\UriInterface;

/**
 * A DTO for storing information related to a usage of an asset.
 */
final class UsageReference
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var UriInterface|null
     */
    private $url;

    public function __construct(string $label, ?UriInterface $url)
    {
        $this->label = $label;
        $this->url = $url;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): ?UriInterface
    {
        return $this->url;
    }

}
