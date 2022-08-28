<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace;

/**
 * The type of variant one dimension space point is compared to another
 *
 * In a variation setup of de -> en; fr
 *
 * One of
 *  * specialization (de -> en)
 *  * generalization (en -> de)
 *  * peer (de <-> fr)
 *  * same (de <-> de)
 *
 * @internal
 */
enum VariantType: string implements \JsonSerializable
{
    case TYPE_SPECIALIZATION = 'specialization';
    case TYPE_GENERALIZATION = 'generalization';
    case TYPE_PEER = 'peer';
    case TYPE_SAME = 'same';

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(VariantType $other): bool
    {
        return $this === $other;
    }
}
