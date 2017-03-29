.. _`Form ViewHelper Reference`:

Form ViewHelper Reference
=========================

This reference was automatically generated from code on 2017-03-29


.. _`Form ViewHelper Reference: neos.form:form`:

neos.form:form
--------------

Custom form ViewHelper that renders the form state instead of referrer fields

:Implementation: Neos\\Form\\ViewHelpers\\FormViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``action`` (string, *optional*): target action

* ``arguments`` (array, *optional*): additional arguments

* ``controller`` (string, *optional*): name of target controller

* ``package`` (string, *optional*): name of target package

* ``subpackage`` (string, *optional*): name of target subpackage

* ``object`` (mixed, *optional*): object to use for the form. Use in conjunction with the "property" attribute on the sub tags

* ``section`` (string, *optional*): The anchor to be added to the action URI (only active if $actionUri is not set)

* ``format`` (string, *optional*): The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)

* ``additionalParams`` (array, *optional*): additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)

* ``absolute`` (boolean, *optional*): If set, an absolute action URI is rendered (only active if $actionUri is not set)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set

* ``fieldNamePrefix`` (string, *optional*): Prefix that will be added to all field names within this form

* ``actionUri`` (string, *optional*): can be used to overwrite the "action" attribute of the form tag

* ``objectName`` (string, *optional*): name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName

* ``useParentRequest`` (boolean, *optional*): If set, the parent Request will be used instead ob the current one

* ``enctype`` (string, *optional*): MIME type with which the form is submitted

* ``method`` (string, *optional*): Transfer type (GET or POST)

* ``name`` (string, *optional*): Name of form

* ``onreset`` (string, *optional*): JavaScript: On reset of the form

* ``onsubmit`` (string, *optional*): JavaScript: On submit of the form

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




.. _`Form ViewHelper Reference: neos.form:form.datePicker`:

neos.form:form.datePicker
-------------------------

Display a jQuery date picker.

Note: Requires jQuery UI to be included on the page.

:Implementation: Neos\\Form\\ViewHelpers\\Form\\DatePickerViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``dateFormat`` (string, *optional*)

* ``enableDatePicker`` (boolean, *optional*)

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``size`` (int, *optional*): The size of the input field

* ``placeholder`` (string, *optional*): Specifies a short hint that describes the expected value of an input element

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``initialDate`` (string, *optional*): Initial date (@see http://www.php.net/manual/en/datetime.formats.php for supported formats)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




.. _`Form ViewHelper Reference: neos.form:form.formElementRootlinePath`:

neos.form:form.formElementRootlinePath
--------------------------------------

Form Element Rootline Path

:Implementation: Neos\\Form\\ViewHelpers\\Form\\FormElementRootlinePathViewHelper




Arguments
*********

* ``renderable`` (Neos\Form\Core\Model\Renderable\RenderableInterface)




.. _`Form ViewHelper Reference: neos.form:form.timePicker`:

neos.form:form.timePicker
-------------------------

Displays two select-boxes for hour and minute selection.

:Implementation: Neos\\Form\\ViewHelpers\\Form\\TimePickerViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``size`` (int, *optional*): The size of the select field

* ``placeholder`` (string, *optional*): Specifies a short hint that describes the expected value of an input element

* ``disabled`` (string, *optional*): Specifies that the select element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``initialDate`` (string, *optional*): Initial time (@see http://www.php.net/manual/en/datetime.formats.php for supported formats)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




.. _`Form ViewHelper Reference: neos.form:form.uploadedImage`:

neos.form:form.uploadedImage
----------------------------

This ViewHelper makes the specified Image object available for its
childNodes.
In case the form is redisplayed because of validation errors, a previously
uploaded image will be correctly used.

:Implementation: Neos\\Form\\ViewHelpers\\Form\\UploadedImageViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``as`` (string, *optional*)

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.




Examples
********

**Example**::

	<f:form.upload property="image" />
	<c:form.uploadedImage property="image" as="theImage">
	  <a href="{f:uri.resource(resource: theImage.resource)}">Link to image resource</a>
	</c:form.uploadedImage>


Expected result::

	<a href="...">Link to image resource</a>




.. _`Form ViewHelper Reference: neos.form:form.uploadedResource`:

neos.form:form.uploadedResource
-------------------------------

This ViewHelper makes the specified PersistentResource available for its
childNodes. If no resource object was found at the specified position,
the child nodes are not rendered.

In case the form is redisplayed because of validation errors, a previously
uploaded resource will be correctly used.

:Implementation: Neos\\Form\\ViewHelpers\\Form\\UploadedResourceViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``as`` (string, *optional*)

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.




Examples
********

**Example**::

	<f:form.upload property="file" />
	<c:form.uploadedResource property="file" as="theResource">
	  <a href="{f:uri.resource(resource: theResource)}">Link to resource</a>
	</c:form.uploadedResource>


Expected result::

	<a href="...">Link to resource</a>




.. _`Form ViewHelper Reference: neos.form:render`:

neos.form:render
----------------

Main Entry Point to render a Form into a Fluid Template

<pre>
{namespace form=Neos\Form\ViewHelpers}
<form:render factoryClass="NameOfYourCustomFactoryClass" />
</pre>

The factory class must implement {@link Neos\Form\Factory\FormFactoryInterface}.

:Implementation: Neos\\Form\\ViewHelpers\\RenderViewHelper




Arguments
*********

* ``persistenceIdentifier`` (string, *optional*): the persistence identifier for the form.

* ``factoryClass`` (string, *optional*): The fully qualified class name of the factory (which has to implement \Neos\Form\Factory\FormFactoryInterface)

* ``presetName`` (string, *optional*): name of the preset to use

* ``overrideConfiguration`` (array, *optional*): factory specific configuration




.. _`Form ViewHelper Reference: neos.form:renderHead`:

neos.form:renderHead
--------------------

Output the configured stylesheets and JavaScript include tags for a given preset

:Implementation: Neos\\Form\\ViewHelpers\\RenderHeadViewHelper




Arguments
*********

* ``presetName`` (string, *optional*): name of the preset to use




.. _`Form ViewHelper Reference: neos.form:renderRenderable`:

neos.form:renderRenderable
--------------------------

Render a renderable

:Implementation: Neos\\Form\\ViewHelpers\\RenderRenderableViewHelper




Arguments
*********

* ``renderable`` (Neos\Form\Core\Model\Renderable\RenderableInterface)




.. _`Form ViewHelper Reference: neos.form:renderValues`:

neos.form:renderValues
----------------------

Renders the values of a form

:Implementation: Neos\\Form\\ViewHelpers\\RenderValuesViewHelper




Arguments
*********

* ``renderable`` (Neos\Form\Core\Model\Renderable\RootRenderableInterface)

* ``as`` (string, *optional*)




.. _`Form ViewHelper Reference: neos.form:translateElementProperty`:

neos.form:translateElementProperty
----------------------------------

ViewHelper to translate the property of a given form element based on its rendering options

:Implementation: Neos\\Form\\ViewHelpers\\TranslateElementPropertyViewHelper




Arguments
*********

* ``property`` (string)

* ``element`` (Neos\Form\Core\Model\FormElementInterface, *optional*)



