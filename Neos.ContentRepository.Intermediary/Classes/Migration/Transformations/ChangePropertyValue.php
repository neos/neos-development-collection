<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Migration\Transformations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\ContentRepository\Intermediary\Domain\Command\SetNodeProperties;
use Neos\ContentRepository\Intermediary\Domain\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Change the value of a given property.
 *
 * This can apply two transformations:
 *
 * If newValue is set, the value will be set to this, with any occurrences of the currentValuePlaceholder replaced with
 * the current value of the property.
 *
 * If search and replace are given, that replacement will be done on the value (after applying the newValue if set).
 */
class ChangePropertyValue implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * @var string
     */
    protected string $propertyName = '';

    /**
     * @var string
     */
    protected string $newValue = '{current}';

    /**
     * @var string
     */
    protected string $search = '';

    /**
     * @var string
     */
    protected string $replace = '';

    /**
     * Placeholder for the current property value to be inserted in newValue.
     *
     * @var string
     */
    protected string $currentValuePlaceholder = '{current}';

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the property to change.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * New property value to be set.
     *
     * The value of the option "currentValuePlaceholder" (defaults to "{current}") will be
     * used to include the current property value into the new value.
     *
     * @param string $newValue
     * @return void
     */
    public function setNewValue(string $newValue)
    {
        $this->newValue = $newValue;
    }

    /**
     * Search string to replace in current property value.
     *
     * @param string $search
     * @return void
     */
    public function setSearch(string $search)
    {
        $this->search = $search;
    }

    /**
     * Replacement for the search string
     *
     * @param string $replace
     * @return void
     */
    public function setReplace(string $replace)
    {
        $this->replace = $replace;
    }

    /**
     * The value of this option (defaults to "{current}") will be used to include the
     * current property value into the new value.
     *
     * @param string $currentValuePlaceholder
     * @return void
     */
    public function setCurrentValuePlaceholder(string $currentValuePlaceholder)
    {
        $this->currentValuePlaceholder = $currentValuePlaceholder;
    }

    public function execute(NodeInterface $node, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($node->hasProperty($this->propertyName)) {
            $currentPropertyValue = $node->getProperty($this->propertyName);
            $newValueWithReplacedCurrentValue = str_replace($this->currentValuePlaceholder, $currentPropertyValue, $this->newValue);
            $newValueWithReplacedSearch = str_replace($this->search, $this->replace, $newValueWithReplacedCurrentValue);

            return $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                PropertyValuesToWrite::fromArray([
                    $this->propertyName => $newValueWithReplacedSearch
                ]),
                UserIdentifier::forSystemUser()
            ));
        }

        return CommandResult::createEmpty();
    }
}

