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
     * @var Tag
     * @ORM\ManyToOne(cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Flow\Lazy
     */
    protected $parent;

    /**
     * @var Collection<\Neos\Media\Domain\Model\Tag>
     * @ORM\OneToMany(mappedBy="parent", orphanRemoval=true)
     * @Flow\Lazy
     */
    protected $children;

    /**
     * @param string $label
     */
    public function __construct($label)
    {
        $this->label = $label;
        $this->assetCollections = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * Sets the label of this tag
     *
     * @param string $label
     * @return void
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * The label of this tag
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Return the asset collections this tag is included in
     *
     * @return Collection
     */
    public function getAssetCollections(): Collection
    {
        return $this->assetCollections;
    }

    /**
     * Set the asset collections that include this tag
     *
     * @param Collection $assetCollections
     * @return void
     */
    public function setAssetCollections(Collection $assetCollections): void
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

    /**
     * @return Tag|null
     */
    public function getParent(): ?Tag
    {
        return $this->parent;
    }

    /**
     * @param Tag $parent
     */
    public function setParent(Tag $parent): void
    {
        $this->parent = $parent;
        $parent->addChild($this);
    }

    /**
     * @param Tag $child
     */
    public function addChild(Tag $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
