<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects\Fixtures\Model;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\CacheAwareInterface;

/**
 * A simple cache aware model
 */
class TestModel implements CacheAwareInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var integer
     */
    protected $counter = 0;

    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Increment and get counter
     *
     * @return integer
     */
    public function getCounter()
    {
        $this->counter++;
        return $this->counter;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->id;
    }
}
