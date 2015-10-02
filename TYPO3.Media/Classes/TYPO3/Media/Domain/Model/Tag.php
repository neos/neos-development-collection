<?php
namespace TYPO3\Media\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Tag, to organize Assets
 *
 * @Flow\Entity
 */
class Tag
{
    /**
     * @var string
     * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
     * @Flow\Validate(type="NotEmpty")
     */
    protected $label;

    /**
     * Constructs tag
     *
     * @param string
     */
    public function __construct($label)
    {
        $this->label = $label;
    }

    /**
     * Sets the label of this tag
     *
     * @param string $label
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * The label of this tag
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
