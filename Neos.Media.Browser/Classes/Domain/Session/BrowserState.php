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
use Neos\Flow\Persistence\Doctrine\PersistenceManager;

/**
 * A container for the state the media browser is in.
 *
 * @Flow\Scope("session")
 */
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
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Set a $value for $key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @Flow\Session(autoStart = true)
     */
    public function set(string $key, $value)
    {
        if (is_object($value) && $entityIdentifier = $this->persistenceManager->getIdentifierByObject($value)) {
            $value = new BrowserStateEntityValue($entityIdentifier, get_class($value));
        }

        if (!isset($this->data[$this->activeAssetSourceIdentifier])) {
            $this->initializeData($this->activeAssetSourceIdentifier);
        }
        $this->data[$this->activeAssetSourceIdentifier][$key] = $value;
    }

    /**
     * Return a value for $key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (!isset($this->data[$this->activeAssetSourceIdentifier])) {
            $this->initializeData($this->activeAssetSourceIdentifier);
        }

        $value = $this->data[$this->activeAssetSourceIdentifier][$key] ?? null;

        if ($value instanceof BrowserStateEntityValue) {
            $value = $this->persistenceManager->getObjectByIdentifier($value->getIdentifier(), $value->getClass());
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getActiveAssetSourceIdentifier(): string
    {
        return $this->activeAssetSourceIdentifier;
    }

    /**
     * @param string $activeAssetSourceIdentifier
     */
    public function setActiveAssetSourceIdentifier(string $activeAssetSourceIdentifier)
    {
        $this->activeAssetSourceIdentifier = $activeAssetSourceIdentifier;
    }

    /**
     * @param string $assetSourceIdentifier
     */
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

    public function __sleep()
    {
        return ['activeAssetSourceIdentifier', 'data'];
    }
}
