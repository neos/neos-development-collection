<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

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
     * @var Collection<\Neos\Media\Domain\Model\AssetCollection>
     * @ORM\ManyToMany(mappedBy="tags", cascade={"persist"})
     * @ORM\OrderBy({"title"="ASC"})
     * @Flow\Lazy
     */
    protected $assetCollections;

    /**
     * @param string $label
     */
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
     * @return Collection
     */
    public function getAssetCollections()
    {
        return $this->assetCollections;
    }

    /**
     * Set the asset collections that include this tag
     *
     * @param Collection $assetCollections
     * @return void
     */
    public function setAssetCollections(Collection $assetCollections)
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
