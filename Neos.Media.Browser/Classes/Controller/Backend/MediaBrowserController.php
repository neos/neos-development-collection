<?php
namespace Neos\Media\Browser\Controller\Backend;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\Controller\Module\Management\AssetController;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Controller for asset handling
 */
class MediaBrowserController extends AssetController
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;
}
