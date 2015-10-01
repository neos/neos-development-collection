<?php
namespace TYPO3\Media\Domain\Session;

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
        'sort' => 'Modified',
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
