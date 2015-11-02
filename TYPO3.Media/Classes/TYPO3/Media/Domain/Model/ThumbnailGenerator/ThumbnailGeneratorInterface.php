<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

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
    public function getPriority();

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
