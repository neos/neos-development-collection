<?php
namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;

/**
 * The HtmlAugmenter Service. It is replaced by the  Neos\Fusion\Service\HtmlAugmenter and therefore deprecated since Neos 3.3.
 * This class will be removed with the release of Neos 4.0.
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class HtmlAugmenter extends FusionHtmlAugmenter
{
}
