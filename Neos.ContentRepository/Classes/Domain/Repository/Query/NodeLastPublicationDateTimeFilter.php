<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Domain\Repository\Query;

class NodeLastPublicationDateTimeFilter extends AbstractNodeDateTimeFilter
{
    protected function getFieldName(): string
    {
        return 'lastPublicationDateTime';
    }
}
