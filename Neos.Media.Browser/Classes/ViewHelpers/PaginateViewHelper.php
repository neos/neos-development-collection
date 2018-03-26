<?php

namespace Neos\Media\Browser\ViewHelpers;

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

use Neos\Media\Browser\AssetSource\AssetProxyQueryResult;
use Neos\Media\Browser\ViewHelpers\Controller\PaginateController;
use Neos\Flow\Annotations\Inject;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;

/**
 * This ViewHelper renders a pagination for asset proxy objects
 *
 * = Examples =
 *
 * <code title="simple configuration">
 * <mediaBrowser:paginate queryResult="{assetProxyQueryResult}" as="assetProxies" configuration="{itemsPerPage: 5}">
 *   // use {assetProxies} as you used {assetProxies} before, most certainly inside
 *   // a <f:for> loop.
 * </f:widget.paginate>
 * </code>
 */
class PaginateViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @Inject
     * @var PaginateController
     */
    protected $controller;

    /**
     * Render this view helper
     *
     * @param AssetProxyQueryResult $queryResult
     * @param string $as
     * @param array $configuration
     * @return string
     */
    public function render(AssetProxyQueryResult $queryResult, $as, array $configuration = array('itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99))
    {
        $response = $this->initiateSubRequest();
        return $response->getContent();
    }
}
