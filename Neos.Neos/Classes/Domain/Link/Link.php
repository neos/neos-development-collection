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
use Neos\Neos\AssetUsage\ProvidesAssetIdsInterface;
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
final readonly class Link implements \JsonSerializable, ProvidesAssetIdsInterface
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
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param array<int, string> $rel
     */
    public static function create(
        UriInterface $href,
        ?string $title = null,
        ?string $target = null,
        array $rel = [],
        bool $download = false,
    ): self {
        $relMap = [];
        foreach ($rel as $value) {
            $relMap[strtolower($value)] = true;
        }
        return new self(
            $href,
            ($title === '' || $title === null) ? null : $title,
            ($target === '' || $target === null) ? null : strtolower($target),
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
            href: new Uri($string)
        );
    }

    /**
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param array<int, string>|null $rel
     */
    public function with(
        ?UriInterface $href = null,
        ?string $title = null,
        ?string $target = null,
        ?array $rel = null,
        ?bool $download = null,
    ): self {
        return self::create(
            $href ?? $this->href,
            $title ?? $this->title,
            $target ?? $this->target,
            $rel ?? $this->rel,
            $download ?? $this->download,
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

    /**
     * @return list<AssetId>
     */
    public function getAssetIds(): array
    {
        $assetId = $this->extractAssetId();
        return $assetId ? [$assetId] : [];
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
