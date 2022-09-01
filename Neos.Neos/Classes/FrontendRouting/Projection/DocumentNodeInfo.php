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

namespace Neos\Neos\FrontendRouting\Projection;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Neos\Domain\Model\SiteNodeName;
use Psr\Http\Message\UriInterface;

/**
 * @Flow\Proxy(false)
 */
final class DocumentNodeInfo
{
    /**
     * @var array<string,mixed>
     */
    private array $source;

    /**
     * @var array<string,mixed>|null
     */
    private ?array $shortcutTarget = null;

    /**
     * @param array<string,mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * @param array<string,string> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self($row);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $source = $this->source;
        $source['dimensionspacepointhash'] = $dimensionSpacePoint->hash;

        return new self($source);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $source = $this->source;
        $source['origindimensionspacepointhash'] = $originDimensionSpacePoint->hash;

        return new self($source);
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return NodeAggregateId::fromString($this->source['nodeaggregateid']);
    }

    public function isRoot(): bool
    {
        return $this->source['parentnodeaggregateid'] === null;
    }

    public function getParentNodeAggregateId(): NodeAggregateId
    {
        return NodeAggregateId::fromString($this->source['parentnodeaggregateid']);
    }

    public function hasPrecedingNodeAggregateId(): bool
    {
        return $this->source['precedingnodeaggregateid'] !== null;
    }

    public function getPrecedingNodeAggregateId(): NodeAggregateId
    {
        return NodeAggregateId::fromString($this->source['precedingnodeaggregateid']);
    }

    public function hasSucceedingNodeAggregateId(): bool
    {
        return $this->source['succeedingnodeaggregateid'] !== null;
    }

    public function getSucceedingNodeAggregateId(): NodeAggregateId
    {
        return NodeAggregateId::fromString($this->source['succeedingnodeaggregateid']);
    }

    public function getDimensionSpacePointHash(): string
    {
        return $this->source['dimensionspacepointhash'];
    }

    /**
     * This is NOT the node path; but the "nodeAggregateIds on the hierarchy; separated by /"
     *
     * @return string
     */
    public function getNodeAggregateIdPath(): string
    {
        return $this->source['nodeaggregateidpath'];
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


    /**
     * As the route tags are based on the node aggregate ids of the node and its parents up to the site,
     * we can extract this from the "nodeAggregateIdPath", which contains these ids.
     *
     * @return RouteTags
     */
    public function getRouteTags(): RouteTags
    {
        $nodeAggregateIds = explode('/', $this->getNodeAggregateIdPath());
        array_shift($nodeAggregateIds); // remove the root node id from the list
        return RouteTags::createFromArray($nodeAggregateIds);
    }

    public function getSiteNodeName(): SiteNodeName
    {
        return SiteNodeName::fromString($this->source['sitenodename']);
    }

    /**
     * @return array<string,mixed>
     */
    public function getShortcutTarget(): array
    {
        if ($this->shortcutTarget === null) {
            try {
                $this->shortcutTarget = json_decode($this->source['shortcuttarget'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf(
                    'Invalid shortcut target "%s": %s',
                    $this->source['shortcuttarget'],
                    $e->getMessage()
                ), 1599036735, $e);
            }
            if ($this->shortcutTarget === null) {
                $this->shortcutTarget = ['mode' => 'none', 'target' => null];
            }
        }
        return $this->shortcutTarget;
    }

    /**
     * @return array<string,string>
     */
    public function toArray(): array
    {
        return $this->source;
    }

    public function __toString(): string
    {
        return ($this->source['nodeaggregateid'] ?? '<unknown nodeAggregateId>')
            . '@' . ($this->source['dimensionspacepointhash'] ?? '<unkown dimensionSpacePointHash>');
    }
}
