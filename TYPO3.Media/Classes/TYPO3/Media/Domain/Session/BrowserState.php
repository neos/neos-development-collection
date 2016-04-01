<?php
namespace TYPO3\Media\Domain\Session;

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

/**
 * A container for the state the media browser is in.
 *
 * @Flow\Scope("session")
 */
class BrowserState
{
    /**
     * @var array
     */
    protected $data = array(
        'activeTag' => null,
        'view' => 'Thumbnail',
        'sortBy' => 'Modified',
        'sortDirection' => 'ASC',
        'filter' => 'All'
    );

    /**
     * Set a $value for $key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Return a value for $key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
