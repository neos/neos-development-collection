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
 * Description for a workspace
 * @Flow\Proxy(false)
 */
class WorkspaceDescription implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $description;

    /**
     * Name constructor.
     *
     * @param string $description
     */
    public function __construct(string $description)
    {
        $this->setDescription($description);
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    /**
     * @param string $description
     */
    protected function setDescription(string $description)
    {
        if (preg_match('/^[\p{L}\p{P}\d \.]{0,500}$/u', $description) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace description given.', 1505831660363);
        }
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->description;
    }
}
