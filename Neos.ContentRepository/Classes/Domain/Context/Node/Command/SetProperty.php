<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\EditingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;

final class SetProperty
{

    /**
     * @var EditingSessionIdentifier
     */
    private $editingSessionIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var mixed
     */
    private $value;

    /**
     * SetProperty constructor.
     *
     * @param EditingSessionIdentifier $editingSessionIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param string $propertyName
     * @param mixed $value
     */
    public function __construct(
        EditingSessionIdentifier $editingSessionIdentifier,
        NodeIdentifier $nodeIdentifier,
        $propertyName,
        $value
    ) {
        $this->editingSessionIdentifier = $editingSessionIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->value = $value;
    }

    /**
     * @return EditingSessionIdentifier
     */
    public function getEditingSessionIdentifier(): EditingSessionIdentifier
    {
        return $this->editingSessionIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
