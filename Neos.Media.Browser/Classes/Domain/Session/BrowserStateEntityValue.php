<?php
declare(strict_types=1);

namespace Neos\Media\Browser\Domain\Session;

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
