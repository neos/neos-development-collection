<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

interface SupportsSorting
{
    /**
     * Constants representing the direction when ordering result sets.
     */
    const ORDER_ASCENDING = 'ASC';
    const ORDER_DESCENDING = 'DESC';

    /**
     * Note: This method is preliminary, not to be used for third-party asset sources yet.
     *
     * @param array $orderings
     * @return void
     */
    public function orderBy(array $orderings): void;
}
