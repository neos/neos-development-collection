<?php
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

class FieldDefinition
{

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $value;

    /**
     * @var bool
     */
    protected $multiple;

    /**
     * @var bool
     */
    protected $validationResult;

    /**
     * FieldDefinition constructor.
     *
     * @param string|null $name
     * @param string|null $value
     * @param bool $multiple
     */
    public function __construct(string $name = null, string $value = null, bool $multiple = false, Result $validationResult = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->multiple = $multiple;
        $this->validationResult = $validationResult;
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return Result|null
     */
    public function getValidationResult(): ?Result
    {
        return $this->validationResult;
    }

}
