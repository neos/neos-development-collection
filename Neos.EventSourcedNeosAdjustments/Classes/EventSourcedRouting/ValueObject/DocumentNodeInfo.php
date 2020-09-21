<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
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

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $source = $this->source;
        $source['dimensionspacepointhash'] = $dimensionSpacePoint->getHash();
        return new static($source);
    }

    public function withOriginDimensionSpacePoint(DimensionSpacePoint $originDimensionSpacePoint): self
    {
        $source = $this->source;
        $source['origindimensionspacepointhash'] = $originDimensionSpacePoint->getHash();
        return new static($source);
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->source['nodeaggregateidentifier']);
    }

    public function isRoot(): bool
    {
        return $this->source['parentnodeaggregateidentifier'] === null;
    }

    public function getParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->source['parentnodeaggregateidentifier']);
    }

    public function hasPrecedingNodeAggregateIdentifier(): bool
    {
        return $this->source['precedingnodeaggregateidentifier'] !== null;
    }

    public function getPrecedingNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->source['precedingnodeaggregateidentifier']);
    }

    public function hasSucceedingNodeAggregateIdentifier(): bool
    {
        return $this->source['succeedingnodeaggregateidentifier'] !== null;
    }

    public function getSucceedingNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->source['succeedingnodeaggregateidentifier']);
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

    public function isDisabled(): bool
    {
        return $this->getDisableLevel() > 0;
    }

    public function getDisableLevel(): int
    {
        return (int)$this->source['disabled'];
    }

    public function getShortcutMode(): string
    {
        if (!$this->isShortcut()) {
            throw new \RuntimeException('Not a shortcut', 1599036543);
        }
        return $this->getShortcutTarget()['mode'] ?? 'none';
    }

    public function getShortcutTargetUri(): UriInterface
    {
        if (!$this->isShortcut()) {
            throw new \RuntimeException('Not a shortcut', 1599036551);
        }
        if (empty($this->getShortcutTarget()['target'])) {
            throw new \RuntimeException('Missing/empty shortcut target', 1599043374);
        }
        return new Uri($this->getShortcutTarget()['target']);
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

    public function getShortcutTarget(): array
    {
        if ($this->shortcutTarget === null) {
            try {
                $this->shortcutTarget = json_decode($this->source['shortcuttarget'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf('Invalid shortcut target "%s": %s', $this->source['shortcuttarget'], $e->getMessage()), 1599036735, $e);
            }
            if ($this->shortcutTarget === null) {
                $this->shortcutTarget = ['mode' => 'none', 'target' => null];
            }
        }
        return $this->shortcutTarget;
    }

    public function toArray(): array
    {
        return $this->source;
    }

    public function __toString(): string
    {
        return ($this->source['nodeaggregateidentifier'] ?? '<unknown nodeAggregateIdentifier>') . '@' . ($this->source['dimensionspacepointhash'] ?? '<unkown dimensionSpacePointHash>');
    }


}
