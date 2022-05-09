<?php
namespace Neos\Neos\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;

/**
 * Describes the mode in which the Neos interface is rendering currently,
 * mainly distinguishing between edit and preview modes currently.
 */
class UserInterfaceMode
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $preview;

    /**
     * @var boolean
     */
    protected $edit;

    /**
     * @var string
     */
    protected $fusionPath;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array<string,mixed>
     */
    protected $options;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isPreview()
    {
        return $this->preview;
    }

    /**
     * @param boolean $preview
     * @return void
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }

    /**
     * @return boolean
     */
    public function isEdit()
    {
        return $this->edit;
    }

    /**
     * @param boolean $edit
     * @return void
     */
    public function setEdit($edit)
    {
        $this->edit = $edit;
    }

    /**
     * @return string
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    /**
     * @param string $fusionPath
     * @return void
     */
    public function setFusionPath($fusionPath)
    {
        $this->fusionPath = $fusionPath;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOptionByPath(string $path): mixed
    {
        return ObjectAccess::getPropertyPath($this->options, $path);
    }

    /**
     * @param array<string,mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Creates an UserInterfaceMode object by configuration
     *
     * @param string $modeName
     * @param array<string,mixed> $configuration
     */
    public static function createByConfiguration($modeName, array $configuration): self
    {
        $mode = new self();
        $mode->setName($modeName);
        $mode->setPreview($configuration['isPreviewMode']);
        $mode->setEdit($configuration['isEditingMode']);
        $mode->setTitle($configuration['title']);

        if (isset($configuration['fusionRenderingPath'])) {
            $mode->setFusionPath($configuration['fusionRenderingPath']);
        } else {
            $mode->setFusionPath('');
        }

        if (isset($configuration['options']) && is_array($configuration['options'])) {
            $mode->setOptions($configuration['options']);
        }

        return $mode;
    }
}
