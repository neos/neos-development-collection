<?php
namespace Neos\Media\ViewHelpers\Form;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper;

/**
 * View Helper which creates a simple checkbox (<input type="checkbox">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <neos.media:form.checkbox name="myCheckBox" value="someValue" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" />
 * </output>
 *
 * <code title="Preselect">
 * <neos.media:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
 * (depending on $object)
 * </output>
 *
 * <code title="Bind to object property">
 * <neos.media:form.checkbox property="interests" value="TYPO3" />
 * </code>
 * <output>
 * <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 * (depending on property "interests")
 * </output>
 *
 * @api
 */
class CheckboxViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->overrideArgument('value', 'mixed', 'Value of input tag. Required for checkboxes', true);
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the checkbox.
     *
     * This is changed to use the actual provided value of the value attribute
     * to support selecting object values.
     *
     * @param boolean $checked Specifies that the input element should be preselected
     * @param boolean $multiple Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)
     *
     * @return string
     * @api
     */
    public function render($checked = null, $multiple = null)
    {
        $this->tag->addAttribute('type', 'checkbox');

        $nameAttribute = $this->getName();
        $valueAttribute = $this->getValue();
        if ($this->isObjectAccessorMode()) {
            if ($this->hasMappingErrorOccurred()) {
                $propertyValue = $this->getLastSubmittedFormData();
            } else {
                $propertyValue = $this->getPropertyValue();
            }

            if ($propertyValue instanceof \Traversable) {
                $propertyValue = iterator_to_array($propertyValue);
            }
            if (is_array($propertyValue)) {
                if ($checked === null) {
                    $checked = in_array($this->arguments['value'], $propertyValue, true);
                }
                $nameAttribute .= '[]';
            } elseif ($multiple === true) {
                $nameAttribute .= '[]';
            } elseif ($checked === null && $propertyValue !== null) {
                $checked = (boolean)$propertyValue === (boolean)$valueAttribute;
            }
        }

        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked) {
            $this->tag->addAttribute('checked', 'checked');
        }

        $this->setErrorClassAttribute();

        $this->renderHiddenFieldForEmptyValue();
        return $this->tag->render();
    }

    /**
     * Get the name of this form element, without prefix.
     *
     * This is done to prevent the extra __identity being added for objects
     * since it leading to property mapping errors and it works without it.
     *
     * @return string name
     */
    protected function getNameWithoutPrefix()
    {
        $name = parent::getNameWithoutPrefix();
        return str_replace('[__identity]', '', $name);
    }
}
