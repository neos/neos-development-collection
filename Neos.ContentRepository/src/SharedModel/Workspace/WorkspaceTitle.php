<?php
declare(strict_types=1);
namespace Neos\ContentRepository\SharedModel\Workspace;

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
 * Title of a workspace
 */
#[Flow\Proxy(false)]
final class WorkspaceTitle implements \JsonSerializable
{
    protected string $title;

    public function __construct(string $title)
    {
        if (preg_match('/^[\p{L}\p{P}\d \.]{1,200}$/u', $title) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace title given.', 1505827170288);
        }
        $this->title = $title;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function jsonSerialize(): string
    {
        return $this->title;
    }
}
