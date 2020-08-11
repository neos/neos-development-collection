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

use Neos\Flow\Annotations as Flow;

/**
 * File Type Icon
 */
final class FileTypeIcon
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="iconSet")
     */
    protected $settings = [];

    /**
     * @var string
     */
    protected $extension;

    public function __construct(string $extension)
    {
        $this->extension = $extension;
    }

    public function alt(): string
    {
        return $this->extension;
    }

    public function path(): string
    {
        $icon = $this->getIconPath($this->extension);

        if (!is_file($icon)) {
            $icon = $this->getIconPath('blank');
        }

        return $icon;
    }

    protected function getIconPath(string $name): string
    {
        return  $this->getIconSet() . '/' . $name . '.' . $this->getIconExtension();
    }

    protected function getIconSet(): string
    {
        return $this->settings['path'] ?? 'resource://Neos.Media/Public/IconSets/vivid';
    }

    protected function getIconExtension(): string
    {
        return $this->settings['extension'] ?? 'svg';
    }
}
