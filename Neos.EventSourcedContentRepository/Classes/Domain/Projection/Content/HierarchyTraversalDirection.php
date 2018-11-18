<?php
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * The hierarchy traversal direction value object
 */
final class HierarchyTraversalDirection
{
    const DIRECTION_UP = 'up';
    const DIRECTION_DOWN = 'down';

    /**
     * @var string
     */
    protected $direction;


    /**
     * @param string $direction
     * @throws Exception\InvalidHierarchyTraversalDirectionException
     */
    public function __construct(string $direction)
    {
        if ($direction !== self::DIRECTION_UP && $direction !== self::DIRECTION_DOWN) {
            throw new Exception\InvalidHierarchyTraversalDirectionException('The given hierarchy traversal direction "' . $direction . '" is neither up nor down.', 1519225166);
        }
        $this->direction = $direction;
    }

    /**
     * @return HierarchyTraversalDirection
     */
    public static function up(): HierarchyTraversalDirection
    {
        return new HierarchyTraversalDirection(self::DIRECTION_UP);
    }

    /**
     * @return HierarchyTraversalDirection
     */
    public static function down(): HierarchyTraversalDirection
    {
        return new HierarchyTraversalDirection(self::DIRECTION_DOWN);
    }

    /**
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->direction === self::DIRECTION_UP;
    }

    /**
     * @return bool
     */
    public function isDown(): bool
    {
        return $this->direction === self::DIRECTION_DOWN;
    }
}
