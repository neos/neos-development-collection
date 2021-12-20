<?php

namespace Neos\Media\Browser\Domain\Session;

class BrowserStateEntityValue
{
    protected $identifier;
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
