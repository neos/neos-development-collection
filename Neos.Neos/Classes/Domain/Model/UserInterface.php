<?php
namespace Neos\Neos\Domain\Model;

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
 * Interface for a user of the content repository. Users can be owners of workspaces.
 *
 * @api
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
