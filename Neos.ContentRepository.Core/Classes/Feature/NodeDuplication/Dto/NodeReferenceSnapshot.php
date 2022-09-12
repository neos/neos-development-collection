<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;

/**
 * @todo what is this?
 * @internal not yet finished
 */
final class NodeReferenceSnapshot
{
    // TODO: actually working??

    private SerializedPropertyValues $values;

    private function __construct(SerializedPropertyValues $values)
    {
        $this->values = $values;
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(SerializedPropertyValues::fromArray($values));
    }

    public function getValues(): SerializedPropertyValues
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function jsonSerialize(): SerializedPropertyValues
    {
        return $this->values;
    }
}
