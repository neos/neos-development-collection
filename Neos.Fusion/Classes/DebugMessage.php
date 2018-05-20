<?php
namespace Neos\Fusion;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A DTO for transporting internal debugging messages
 */
class DebugMessage
{
    /**
     * @var int
     */
    protected $level;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var bool
     */
    protected $plaintext;

    public function __construct(string $title, string $path, $data, bool $plaintext, int $level = LOG_DEBUG)
    {
        $this->title = $title;
        $this->path = $path;
        $this->data = $data;
        $this->plaintext = $plaintext;
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isPlaintext(): bool
    {
        return $this->plaintext;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }
}
