<?php

namespace Neos\Media\Browser\ViewHelpers;

/*
* This file is part of the Neos.Media.Browser package.
*
* (c) Contributors of the Neos Project - www.neos.io
*
* This package is Open Source Software. For the full copyright and license
* information, please view the LICENSE file which was distributed with this
* source code.
*/

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;
use Neos\Media\Browser\ViewHelpers\Controller\PaginateController;
use Neos\Media\Domain\Model\AssetSource\AssetProxyQueryResultInterface;

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
     * @Flow\Inject
     * @var PaginateController
     */
    protected $controller;

    /**
     * Render this view helper
     *
     * @param AssetProxyQueryResultInterface $queryResult
     * @param string $as
     * @param array $configuration
     * @return string
     * @throws \Neos\Flow\Mvc\Exception\InfiniteLoopException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\InvalidControllerException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\MissingControllerException
     */
    public function render(AssetProxyQueryResultInterface $queryResult, $as, array $configuration = ['itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99])
    {
        $response = $this->initiateSubRequest();
        return $response->getContent();
    }
}
