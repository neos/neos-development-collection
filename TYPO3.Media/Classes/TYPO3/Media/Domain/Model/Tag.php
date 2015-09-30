<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var \Doctrine\Common\Collections\Collection<\TYPO3\Media\Domain\Model\AssetCollection>
     * @ORM\ManyToMany(mappedBy="tags", cascade={"persist"})
     * @ORM\OrderBy({"title"="ASC"})
     * @Flow\Lazy
     */
    protected $assetCollections;

    public function __construct($label)
    {
        $this->label = $label;
        $this->assetCollections = new ArrayCollection();
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

    /**
     * Return the asset collections this tag is included in
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssetCollections()
    {
        return $this->assetCollections;
    }

    /**
     * Set the asset collections that include this tag
     *
     * @param \Doctrine\Common\Collections\Collection $assetCollections
     * @return void
     */
    public function setAssetCollections(\Doctrine\Common\Collections\Collection $assetCollections)
    {
        foreach ($this->assetCollections as $existingAssetCollection) {
            $existingAssetCollection->removeTag($this);
        }
        foreach ($assetCollections as $newAssetCollection) {
            $newAssetCollection->addTag($this);
        }
        foreach ($this->assetCollections as $assetCollection) {
            if (!$assetCollections->contains($assetCollection)) {
                $assetCollections->add($assetCollection);
            }
        }
        $this->assetCollections = $assetCollections;
    }
}
