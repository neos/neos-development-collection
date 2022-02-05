<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * TODO: can be removed, moving works differently now.
 *
 * Position of a reference (when moving or copying)
 * @Flow\Proxy(false)
 */
final class ReferencePosition implements \JsonSerializable
{
    const BEFORE = 'before';
    const INTO = 'into';
    const AFTER = 'after';

    /**
     * @var string
     */
    private $position;

    public static function before(): ReferencePosition
    {
        return new static(static::BEFORE);
    }

    public static function into(): ReferencePosition
    {
        return new static(static::INTO);
    }

    public static function after(): ReferencePosition
    {
        return new static(static::AFTER);
    }

    public function __construct(string $position)
    {
        if ($position !== static::BEFORE && $position !== static::INTO && $position !== static::AFTER) {
            throw new \InvalidArgumentException(sprintf('Invalid position: "%s"', $position), 1506002748);
        }
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->position;
    }
}
