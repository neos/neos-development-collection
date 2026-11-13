<?php

namespace Neos\Media\Browser\Domain\Session;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A container for the state the media browser is in.
 */
#[Flow\Scope('session')]
class BrowserState
{
    /**
     * @var string
     */
    protected $activeAssetSourceIdentifier = 'neos';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Set a $value for $key
     *
     * @param mixed $value
     * @return void
     */
    #[Flow\Session(autoStart: true)]
    public function set(string $key, $value)
    {
        if (!isset($this->data[$this->activeAssetSourceIdentifier])) {
            $this->initializeData($this->activeAssetSourceIdentifier);
        }
        $this->data[$this->activeAssetSourceIdentifier][$key] = $value;
    }

    /**
     * Return a value for $key.
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if (!isset($this->data[$this->activeAssetSourceIdentifier])) {
            $this->initializeData($this->activeAssetSourceIdentifier);
        }
        return $this->data[$this->activeAssetSourceIdentifier][$key] ?? null;
    }

    public function getActiveAssetSourceIdentifier(): string
    {
        return $this->activeAssetSourceIdentifier;
    }

    public function setActiveAssetSourceIdentifier(string $activeAssetSourceIdentifier)
    {
        $this->activeAssetSourceIdentifier = $activeAssetSourceIdentifier;
    }

    private function initializeData(string $assetSourceIdentifier)
    {
        $this->data[$assetSourceIdentifier] = [
            'activeTag' => null,
            'view' => 'Thumbnail',
            'sortBy' => 'Modified',
            'sortDirection' => 'DESC',
            'filter' => 'All'
        ];
    }
}
