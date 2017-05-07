<?php
namespace Neos\ContentRepository\Domain\Model;

interface ReadablePropertiesInterface
{
    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasProperty($propertyName);

    /**
     * @param string $propertyName
     * @return mixed
     */
    public function getProperty($propertyName);

    /**
     * @return array
     */
    public function getProperties();

    /**
     * @return array
     */
    public function getPropertyNames();
}
