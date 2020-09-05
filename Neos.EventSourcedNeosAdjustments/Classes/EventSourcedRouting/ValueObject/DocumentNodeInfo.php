<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Psr\Http\Message\UriInterface;

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
 * @Flow\Proxy(false)
 */
final class DocumentNodeInfo
{
    /**
     * @var array
     */
    private $source;

    /**
     * @var array|null
     */
    private $shortcutTarget;

    public function __construct(array $source)
    {
        $this->source = $source;
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new static($row);
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->source['nodeaggregateidentifier']);
    }

    public function getDimensionSpacePointHash(): string
    {
        return $this->source['dimensionspacepointhash'];
    }

    public function getNodePath(): string
    {
        return $this->source['nodepath'];
    }

    public function getUriPath(): string
    {
        return $this->source['uripath'];
    }

    public function hasUriPath(): bool
    {
        return !empty($this->source['uripath']);
    }

    public function isShortcut(): bool
    {
        return isset($this->source['shortcuttarget']);
    }

    public function getShortcutMode(): string
    {
        if (!$this->isShortcut()) {
            throw new \RuntimeException('Not a shortcut', 1599036543);
        }
        return $this->shortcutTarget()['mode'] ?? 'none';
    }

    public function getShortcutTarget(): UriInterface
    {
        if (!$this->isShortcut()) {
            throw new \RuntimeException('Not a shortcut', 1599036551);
        }
        if (empty($this->shortcutTarget()['target'])) {
            throw new \RuntimeException('Missing/empty shortcut target', 1599043374);
        }
        return new Uri($this->shortcutTarget()['target']);
    }

    public function getRouteTags(): RouteTags
    {
        $nodeAggregateIdentifiers = explode('/', $this->getNodePath());
        array_shift($nodeAggregateIdentifiers);
        return RouteTags::createFromArray($nodeAggregateIdentifiers);
    }

    public function getSiteNodeName(): NodeName
    {
        return NodeName::fromString($this->source['sitenodename']);
    }

    private function shortcutTarget(): array
    {
        if ($this->shortcutTarget === null) {
            try {
                $this->shortcutTarget = json_decode($this->source['shortcuttarget'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf('Invalid shortcut target "%s": %s', $this->source['shortcuttarget'], $e->getMessage()), 1599036735, $e);
            }
            if ($this->shortcutTarget === null) {
                $this->shortcutTarget = ['mode' => 'none', 'target' => ''];
            }
        }
        return $this->shortcutTarget;
    }
}
