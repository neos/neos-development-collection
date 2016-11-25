<?php
namespace Neos\ContentRepository\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 *
 * @Flow\Scope("singleton")
 * @deprecated since 2.1, use \Neos\ContentRepository\Domain\Service\PublishingService instead
 */
class PublishingService extends \Neos\ContentRepository\Domain\Service\PublishingService
{
}
