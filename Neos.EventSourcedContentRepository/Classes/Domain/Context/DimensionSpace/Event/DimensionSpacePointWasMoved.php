<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;

/**
 * Moved a dimension space point to a new location; basically moving all content to the new dimension space point.
 *
 * This is used to *rename* dimension space points, e.g. from "de" to "de_DE".
 *
 * NOTE: the target dimension space point must not contain any content.
 */
final class DimensionSpacePointWasMoved implements DomainEventInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private $source;

    /**
     * @var DimensionSpacePoint
     */
    private $target;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $source, DimensionSpacePoint $target)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->source = $source;
        $this->target = $target;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getSource(): DimensionSpacePoint
    {
        return $this->source;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getTarget(): DimensionSpacePoint
    {
        return $this->target;
    }
}
