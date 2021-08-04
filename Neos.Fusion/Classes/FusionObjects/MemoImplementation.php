<?php
declare(strict_types=1);
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Memo object that returns the result of previous calls with the same "discriminator"
 */
class MemoImplementation extends AbstractFusionObject
{
    protected static $cache = [];

    /**
     * Return the processed value or its cached version based on the discriminator
     *
     * @return mixed
     */
    public function evaluate()
    {
        $discriminator = $this->getDiscriminator();
        if (array_key_exists($discriminator, self::$cache)) {
            return self::$cache[$discriminator];
        }

        $value = $this->getValue();
        self::$cache[$discriminator] = $value;

        return $value;
    }

    public function getDiscriminator(): string
    {
        return (string)$this->fusionValue('discriminator');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->fusionValue('value');
    }
}
