<?php

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Link;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetId;
use Psr\Http\Message\UriInterface;

/**
 * Link value object modeled partially after the html spec for <a> tags.
 *
 * Note that currently the link editor can only handle and write to
 * the property {@see Link::$target} "_blank" | null
 * and to {@see Link::$rel} ["noopener"] | null
 *
 * The Link values can be accessed in Fusion the following:
 *
 * ```fusion
 * href = ${q(node).property("link").href}
 * title = ${q(node).property("link").title}
 * # ...
 * ```
 *
 * In case you need to cast the uri in {@see Link::$href} explicitly to a string
 * you can use: `String.toString(link.href)`
 *
 * @Flow\Proxy(false)
 */
final readonly class Link implements \JsonSerializable
{
    /**
     * A selection of frequently used target attribute values
     */
    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';

    /**
     * A selection of frequently used rel attribute values
     */
    public const REL_NOOPENER = 'noopener';
    public const REL_NOFOLLOW = 'nofollow';

    /**
     * @param array<int, string> $rel
     */
    private function __construct(
        public UriInterface $href,
        public ?string $title,
        public ?string $target,
        public array $rel,
        public bool $download
    ) {
    }

    /**
     * @param array<int, string> $rel
     */
    public static function create(
        UriInterface $href,
        ?string $title,
        ?string $target,
        array $rel,
        bool $download,
    ): self {
        $relMap = [];
        foreach ($rel as $value) {
            $relMap[trim(strtolower($value))] = true;
        }
        $trimmedTarget = $target !== null ? trim($target) : '';
        return new self(
            $href,
            $title,
            $trimmedTarget !== '' ? strtolower($trimmedTarget) : null,
            array_keys($relMap),
            $download
        );
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return self::create(
            new Uri($array['href']),
            $array['title'] ?? null,
            $array['target'] ?? null,
            $array['rel'] ?? [],
            $array['download'] ?? false,
        );
    }

    public static function fromString(string $string): self
    {
        return self::create(
            new Uri($string),
            null,
            null,
            [],
            false,
        );
    }

    public function withTitle(?string $title): self
    {
        return self::create(
            $this->href,
            $title,
            $this->target,
            $this->rel,
            $this->download,
        );
    }

    public function withTarget(?string $target): self
    {
        return self::create(
            $this->href,
            $this->title,
            $target,
            $this->rel,
            $this->download,
        );
    }

    /**
     * @param array<int, string> $rel
     */
    public function withRel(array $rel): self
    {
        return self::create(
            $this->href,
            $this->title,
            $this->target,
            $rel,
            $this->download,
        );
    }

    public function withDownload(bool $download): self
    {
        return self::create(
            $this->href,
            $this->title,
            $this->target,
            $this->rel,
            $download,
        );
    }

    public function extractForScheme(string $scheme): string|null
    {
        if ($this->href->getScheme() !== $scheme) {
            return null;
        }
        return $this->href->getHost() . $this->href->getPath();
    }

    public function extractNodeAggregateId(): ?NodeAggregateId
    {
        $rawNodeId = $this->extractForScheme('node');
        return $rawNodeId ? NodeAggregateId::fromString($rawNodeId) : null;
    }

    public function extractAssetId(): ?AssetId
    {
        $rawAssetId = $this->extractForScheme('asset');
        return $rawAssetId ? AssetId::fromString($rawAssetId) : null;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'href' => $this->href->__toString(),
            'title' => $this->title,
            'target' => $this->target,
            'rel' => $this->rel,
            'download' => $this->download,
        ];
    }
}
