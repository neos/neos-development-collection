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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
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

    /**
     * This is NOT the node path; but the "nodeAggregateIdentifiers on the hierarchy; separated by /"
     *
     * @return string
     */
    public function getNodeAggregateIdentifierPath(): string
    {
        return $this->source['nodeaggregateidentifierpath'];
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
     * As the route tags are based on the node aggregate identifiers of the node and its parents up to the site,
     * we can extract this from the "nodeAggregateIdentifierPath", which contains these identifiers.
     *
     * @return RouteTags
     */
    public function getRouteTags(): RouteTags
    {
        $nodeAggregateIdentifiers = explode('/', $this->getNodeAggregateIdentifierPath());
        array_shift($nodeAggregateIdentifiers); // remove the root node identifier from the list
        return RouteTags::createFromArray($nodeAggregateIdentifiers);
    }

    // TODO: maybe return SiteIdentifier here?
    // TODO: name clash "Site Identifier" and "SiteNodeName"?
    public function getSiteNodeName(): NodeName
    {
        return NodeName::fromString($this->source['sitenodename']);
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
        return ($this->source['nodeaggregateidentifier'] ?? '<unknown nodeAggregateIdentifier>')
            . '@' . ($this->source['dimensionspacepointhash'] ?? '<unkown dimensionSpacePointHash>');
    }
}
