<?php
declare(strict_types=1);

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

class BrowserStateEntityValue
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $class;

    public function __construct(string $identifier, string $class)
    {
        $this->identifier = $identifier;
        $this->class = $class;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
