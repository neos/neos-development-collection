<?php

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
 * @Flow\Proxy(false)
 */
final class VariantType implements \JsonSerializable
{
    const TYPE_SPECIALIZATION = 'specialization';

    const TYPE_GENERALIZATION = 'generalization';

    const TYPE_PEER = 'peer';

    const TYPE_SAME = 'same';

    /**
     * @var string
     */
    private $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function specialization(): VariantType
    {
        return new VariantType(self::TYPE_SPECIALIZATION);
    }

    public static function generalization(): VariantType
    {
        return new VariantType(self::TYPE_GENERALIZATION);
    }

    public static function peer(): VariantType
    {
        return new VariantType(self::TYPE_PEER);
    }

    public static function same(): VariantType
    {
        return new VariantType(self::TYPE_SAME);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function equals(VariantType $other): bool
    {
        return $this->type === $other->getType();
    }
}
