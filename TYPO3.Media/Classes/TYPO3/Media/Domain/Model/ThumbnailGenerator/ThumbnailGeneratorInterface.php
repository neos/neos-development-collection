<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Media\Domain\Model\Thumbnail;

/**
 * Thumbnail Generate Interface
 */
interface ThumbnailGeneratorInterface
{
    /**
     * Return the priority of this ThumbnailGenerator. ThumbnailGenerator with a high priority are chosen before low priority.
     *
     * @return integer
     * @api
     */
    public static function getPriority();

    /**
     * @param Thumbnail $thumbnail
     * @return boolean TRUE if this ThumbnailGenerator can convert the given thumbnail, FALSE otherwise.
     * @api
     */
    public function canRefresh(Thumbnail $thumbnail);

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @api
     */
    public function refresh(Thumbnail $thumbnail);
}
