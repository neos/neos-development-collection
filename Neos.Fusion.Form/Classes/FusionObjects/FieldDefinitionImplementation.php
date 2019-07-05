<?php
declare(strict_types=1);

namespace Neos\Fusion\Form\FusionObjects;

/*
 * This file is part of the Neos.Fusion.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\Form\Domain\Model\FormDefinition;
use Neos\Fusion\Form\Domain\Model\FieldDefinition;
use Neos\Utility\ObjectAccess;

/**
 * Class FormFieldImplementation
 * @package Neos\Fusion\Form\FusionObjects
 */
class FieldDefinitionImplementation extends AbstractFusionObject
{
    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @return FieldDefinition
     */
    public function evaluate(): FieldDefinition
    {
        $formValue = $this->fusionValue('form');
        if ($formValue && $formValue instanceof FormDefinition) {
            $form = $formValue;
        } else {
            $form = null;
        }

        return new FieldDefinition(
            $this->getName($form),
            $this->getValue($form)
        );
    }

    /**
     * Get the name of this form element.
     * Either returns the defined 'name', or the correct name for Object Access.
     *
     * If the property is
     *
     * @return string Name
     */
    public function getName(FormDefinition $form = null): string
    {
        $path = explode('.', $this->getPropertyPath($form));
        $name = array_shift($path);
        foreach ($path as $segment) {
            $name .= '[' . $segment . ']';
        }
        return $name;
    }

    /**
     * @return string|array|null
     */
    public function getValue(FormDefinition $form = null)
    {
        $value = null;
        $propertyPath = $this->getPropertyPath($form);

        if ($form && $form->getMappingResults() !== null && $form->getMappingResults()->hasErrors()) {
            $value = ObjectAccess::getPropertyPath($form->getSubmittedValues(), $propertyPath);
        }

        if ($value == null) {
            if ($fusionValue = $this->fusionValue('value')) {
                $value = $fusionValue;
            } elseif ($form && $this->fusionValue('property')) {
                $value = ObjectAccess::getPropertyPath($form->getObject(), $this->fusionValue('property'));
            }
        }

        if (is_object($value)) {
            $identifier = $this->persistenceManager->getIdentifierByObject($value);
            if ($identifier !== null) {
                $value = $identifier;
            }
        }

        if (is_array($value)) {
            return $value;
        } else {
            return (string)$value;
        }
    }

    /**
     * Returns the "absolute" property path of the property bound to this field as array.
     *
     * For property="foo.bar" will be "<formObjectName>.foo.bar"
     * For name="foo[bar][baz]" this will be "foo.bar.baz"
     *
     * @return string
     */
    protected function getPropertyPath(FormDefinition $form = null): string
    {
        // calculate path
        if ($property = $this->fusionValue('property')) {
            $path = $property;
        } else {
            $path = preg_replace('/(\]\[|\[|\])/', '.', $this->fusionValue('name'));
        }

        // prefix form name and fieldNamePrefix if needed
        if ($path && $form){
            if ($prefix = $form->getFieldNamePrefix()) {
                return sprintf(
                    '%s.%s.%s' ,
                    $form->getFieldNamePrefix(),
                    $form->getName(),
                    $path
                );

            } else {
                return sprintf(
                    '%s.%s' ,
                    $form->getName(),
                    $path
                );
            }
        }

        return $path;
    }

}
