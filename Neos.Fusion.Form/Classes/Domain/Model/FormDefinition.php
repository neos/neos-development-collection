<?php
declare(strict_types=1);

namespace Neos\Fusion\Form\Domain\Model;

/*
 * This file is part of the Neos.Fusion.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;

/**
 * Used to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 */
class FormDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $object;

    /**
     * @var string
     */
    protected $fieldNamePrefix;

    /**
     * @var array
     */
    protected $submittedValues;

    /**
     * @var Result
     */
    protected $mappingResults;

    /**
     * FormDefinition constructor.
     * @param string|null $name
     * @param object|null $object
     * @param string|null $fieldNamePrefix
     * @param array|null $submittedValues
     * @param Result|null $mappingResults
     */
    public function __construct(string $name = null,object $object = null, string $fieldNamePrefix = null,  array$submittedValues = null, Result $mappingResults = null)
    {
        $this->name = $name;
        $this->object = $object;
        $this->fieldNamePrefix = $fieldNamePrefix;
        $this->submittedValues = $submittedValues;
        $this->mappingResults = $mappingResults;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFieldNamePrefix(): ?string
    {
        return $this->fieldNamePrefix;
    }

    /**
    * @return mixed
    */
    public function getObject(): ?object
    {
        return $this->object;
    }

    /**
     * @return array
     */
    public function getSubmittedValues(): ?array
    {
        return $this->submittedValues;
    }

    /**
     * @return Result
     */
    public function getMappingResults(): ?Result
    {
        return $this->mappingResults;
    }
}
