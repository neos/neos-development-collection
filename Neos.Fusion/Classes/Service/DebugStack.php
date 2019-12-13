<?php
namespace Neos\Fusion\Service;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\DebugMessage;

/**
 * @Flow\Scope("singleton")
 */
class DebugStack
{
    /**
     * @var DebugMessage[]
     */
    protected $data = [];

    public function register(DebugMessage $data)
    {
        $this->data[] = $data;
    }

    public function hasMessage(): bool
    {
        return count($this->data) > 0;
    }

    public function dump()
    {
        $data = $this->data;
        $this->flush();
        $output = '';
        foreach ($data as $debugMessage) {
            $output .= \Neos\Flow\var_dump($debugMessage->getData(), $debugMessage->getTitle(), true, $debugMessage->isPlaintext());
        }
        return $output;
    }

    public function flush()
    {
        $this->data = [];
    }
}
