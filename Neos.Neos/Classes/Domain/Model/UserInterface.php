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

namespace Neos\Neos\Domain\Model;

/**
 * @deprecated with 9.0.0-beta14 please use {@see \Neos\Neos\Domain\Model\User} instead.
 * The interface was only needed for the old cr: https://github.com/neos/neos-development-collection/pull/165#issuecomment-157645872
 */
interface UserInterface
{
    /**
     * Returns a label which can be used as a human-friendly identifier for this user, for example his or her first
     * and last name.
     *
     * @return string
     */
    public function getLabel();
}
