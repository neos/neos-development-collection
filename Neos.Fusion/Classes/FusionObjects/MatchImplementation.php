<?php
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

use Neos\Fusion\Exception as FusionException;

/**
 * Implementation class for matching strings in Fusion and return the matched value
 */
class MatchImplementation extends AbstractFusionObject
{
    public function getSubject(): string
    {
        return (string)($this->fusionValue('__meta/subject') ?? '');
    }

    public function getDefault(): ?string
    {
        return $this->fusionValue('__meta/default');
    }

    /**
     * Tries to find a matching value for the subject and returns it
     *
     * @return mixed
     * @throws FusionException
     */
    public function evaluate()
    {
        $subject = $this->getSubject();
        $result = $this->fusionValue($subject);

        if ($result !== null) {
            return $result;
        }

        $default = $this->getDefault();
        if ($default !== null) {
            return $default;
        }

        throw new FusionException('Unhandled match', 1616578975);
    }
}
