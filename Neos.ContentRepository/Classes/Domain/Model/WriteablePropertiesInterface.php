<?php
namespace Neos\ContentRepository\Domain\Model;

interface WriteablePropertiesInterface
{
    /**
     * @param string $propertyName
     * @param mixed $value
     * @return void
     */
    public function setProperty($propertyName, $value);

    /**
     * @param string $propertyName
     * @return void
     */
    public function removeProperty($propertyName);

}
