.. _`FluidAdaptor ViewHelper Reference`:

FluidAdaptor ViewHelper Reference
=================================

This reference was automatically generated from code on 2017-03-29


.. _`FluidAdaptor ViewHelper Reference: f:base`:

f:base
------

View helper which creates a <base href="..." /> tag. The Base URI
is taken from the current request.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\BaseViewHelper





Examples
********

**Example**::

	<f:base />


Expected result::

	<base href="http://yourdomain.tld/" />
	(depending on your domain)




.. _`FluidAdaptor ViewHelper Reference: f:debug`:

f:debug
-------

View helper that outputs its child nodes with \Neos\Flow\var_dump()

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\DebugViewHelper




Arguments
*********

* ``title`` (string, *optional*)

* ``typeOnly`` (boolean, *optional*): Whether only the type should be returned instead of the whole chain.




Examples
********

**inline notation and custom title**::

	{object -> f:debug(title: 'Custom title')}


Expected result::

	all properties of {object} nicely highlighted (with custom title)


**only output the type**::

	{object -> f:debug(typeOnly: true)}


Expected result::

	the type or class name of {object}




.. _`FluidAdaptor ViewHelper Reference: f:flashMessages`:

f:flashMessages
---------------

View helper which renders the flash messages (if there are any) as an unsorted list.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\FlashMessagesViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``as`` (string, *optional*): The name of the current flashMessage variable for rendering inside

* ``severity`` (string, *optional*): severity of the messages (One of the \Neos\Error\Messages\Message::SEVERITY_* constants)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Simple**::

	<f:flashMessages />


Expected result::

	<ul>
	  <li class="flashmessages-ok">Some Default Message</li>
	  <li class="flashmessages-warning">Some Warning Message</li>
	</ul>


**Output with css class**::

	<f:flashMessages class="specialClass" />


Expected result::

	<ul class="specialClass">
	  <li class="specialClass-ok">Default Message</li>
	  <li class="specialClass-notice"><h3>Some notice message</h3>With message title</li>
	</ul>


**Output flash messages as a list, with arguments and filtered by a severity**::

	<f:flashMessages severity="Warning" as="flashMessages">
		<dl class="messages">
		<f:for each="{flashMessages}" as="flashMessage">
			<dt>{flashMessage.code}</dt>
			<dd>{flashMessage}</dd>
		</f:for>
		</dl>
	</f:flashMessages>


Expected result::

	<dl class="messages">
		<dt>1013</dt>
		<dd>Some Warning Message.</dd>
	</dl>




.. _`FluidAdaptor ViewHelper Reference: f:form`:

f:form
------

Used to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\FormViewHelper




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




Examples
********

**Basic usage, POST method**::

	<f:form action="...">...</f:form>


Expected result::

	<form action="...">...</form>


**Basic usage, GET method**::

	<f:form action="..." method="get">...</f:form>


Expected result::

	<form method="GET" action="...">...</form>


**Form with a sepcified encoding type**::

	<f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>


Expected result::

	<form enctype="multipart/form-data" action="...">...</form>


**Binding a domain object to a form**::

	<f:form action="..." name="customer" object="{customer}">
	  <f:form.hidden property="id" />
	  <f:form.textfield property="name" />
	</f:form>


Expected result::

	A form where the value of {customer.name} is automatically inserted inside the textbox; the name of the textbox is
	set to match the property name.




.. _`FluidAdaptor ViewHelper Reference: f:form.button`:

f:form.button
-------------

Creates a button.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\ButtonViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``type`` (string, *optional*): Specifies the type of button (e.g. "button", "reset" or "submit")

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``autofocus`` (string, *optional*): Specifies that a button should automatically get focus when the page loads

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``form`` (string, *optional*): Specifies one or more forms the button belongs to

* ``formaction`` (string, *optional*): Specifies where to send the form-data when a form is submitted. Only for type="submit"

* ``formenctype`` (string, *optional*): Specifies how form-data should be encoded before sending it to a server. Only for type="submit" (e.g. "application/x-www-form-urlencoded", "multipart/form-data" or "text/plain")

* ``formmethod`` (string, *optional*): Specifies how to send the form-data (which HTTP method to use). Only for type="submit" (e.g. "get" or "post")

* ``formnovalidate`` (string, *optional*): Specifies that the form-data should not be validated on submission. Only for type="submit"

* ``formtarget`` (string, *optional*): Specifies where to display the response after submitting the form. Only for type="submit" (e.g. "_blank", "_self", "_parent", "_top", "framename")

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Defaults**::

	<f:form.button>Send Mail</f:form.button>


Expected result::

	<button type="submit" name="" value="">Send Mail</button>


**Disabled cancel button with some HTML5 attributes**::

	<f:form.button type="reset" name="buttonName" value="buttonValue" disabled="disabled" formmethod="post" formnovalidate="formnovalidate">Cancel</f:form.button>


Expected result::

	<button disabled="disabled" formmethod="post" formnovalidate="formnovalidate" type="reset" name="myForm[buttonName]" value="buttonValue">Cancel</button>




.. _`FluidAdaptor ViewHelper Reference: f:form.checkbox`:

f:form.checkbox
---------------

View Helper which creates a simple checkbox (<input type="checkbox">).

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\CheckboxViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``checked`` (boolean, *optional*): Specifies that the input element should be preselected

* ``multiple`` (boolean, *optional*): Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed): Value of input tag. Required for checkboxes

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.checkbox name="myCheckBox" value="someValue" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" />


**Preselect**::

	<f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
	(depending on $object)


**Bind to object property**::

	<f:form.checkbox property="interests" value="TYPO3" />


Expected result::

	<input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
	(depending on property "interests")




.. _`FluidAdaptor ViewHelper Reference: f:form.hidden`:

f:form.hidden
-------------

Renders an <input type="hidden" ...> tag.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\HiddenViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.hidden name="myHiddenValue" value="42" />


Expected result::

	<input type="hidden" name="myHiddenValue" value="42" />




.. _`FluidAdaptor ViewHelper Reference: f:form.password`:

f:form.password
---------------

View Helper which creates a simple Password Text Box (<input type="password">).

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\PasswordViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``required`` (boolean, *optional*): If the field is required or not

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``maxlength`` (int, *optional*): The maxlength attribute of the input field (will not be validated)

* ``readonly`` (string, *optional*): The readonly attribute of the input field

* ``size`` (int, *optional*): The size of the input field

* ``placeholder`` (string, *optional*): The placeholder of the input field

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.password name="myPassword" />


Expected result::

	<input type="password" name="myPassword" value="default value" />




.. _`FluidAdaptor ViewHelper Reference: f:form.radio`:

f:form.radio
------------

View Helper which creates a simple radio button (<input type="radio">).

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\RadioViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``checked`` (boolean, *optional*): Specifies that the input element should be preselected

* ``name`` (string, *optional*): Name of input tag

* ``value`` (string): Value of input tag. Required for radio buttons

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.radio name="myRadioButton" value="someValue" />


Expected result::

	<input type="radio" name="myRadioButton" value="someValue" />


**Preselect**::

	<f:form.radio name="myRadioButton" value="someValue" checked="{object.value} == 5" />


Expected result::

	<input type="radio" name="myRadioButton" value="someValue" checked="checked" />
	(depending on $object)


**Bind to object property**::

	<f:form.radio property="newsletter" value="1" /> yes
	<f:form.radio property="newsletter" value="0" /> no


Expected result::

	<input type="radio" name="user[newsletter]" value="1" checked="checked" /> yes
	<input type="radio" name="user[newsletter]" value="0" /> no
	(depending on property "newsletter")




.. _`FluidAdaptor ViewHelper Reference: f:form.select`:

f:form.select
-------------

This ViewHelper generates a <select> dropdown list for the use with a form.

**Basic usage**

The most straightforward way is to supply an associative array as the "options" parameter.
The array key is used as option key, and the array value is used as human-readable name.

To pre-select a value, set "value" to the option key which should be selected. If the select box is a multi-select
box (multiple="true"), then "value" can be an array as well.

**Usage on domain objects**

If you want to output domain objects, you can just pass them as array into the "options" parameter.
To define what domain object value should be used as option key, use the "optionValueField" variable. Same goes for optionLabelField.
If neither is given, the Identifier (UUID/uid) and the __toString() method are tried as fallbacks.

If the optionValueField variable is set, the getter named after that value is used to retrieve the option key.
If the optionLabelField variable is set, the getter named after that value is used to retrieve the option value.

If the prependOptionLabel variable is set, an option item is added in first position, bearing an empty string
or - if specified - the value of the prependOptionValue variable as value.

In the example below, the userArray is an array of "User" domain objects, with no array key specified. Thus the
method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve the displayed value of
each entry. The "value" property now expects a domain object, and tests for object equivalence.

**Translation of select content**

The ViewHelper can be given a "translate" argument with configuration on how to translate option labels.
The array can have the following keys:
- "by" defines if translation by message id or original label is to be used ("id" or "label")
- "using" defines if the option tag's "value" or "label" should be used as translation input, defaults to "value"
- "locale" defines the locale identifier to use, optional, defaults to current locale
- "source" defines the translation source name, optional, defaults to "Main"
- "package" defines the package key of the translation source, optional, defaults to current package
- "prefix" defines a prefix to use for the message id – only works in combination with "by id"

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\SelectViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``multiple`` (string, *optional*): if set, multiple select field

* ``size`` (string, *optional*): Size of input field

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``options`` (array): Associative array with internal IDs as key, and the values are displayed in the select box

* ``optionValueField`` (string, *optional*): If specified, will call the appropriate getter on each object to determine the value.

* ``optionLabelField`` (string, *optional*): If specified, will call the appropriate getter on each object to determine the label.

* ``sortByOptionLabel`` (boolean, *optional*): If true, List will be sorted by label.

* ``selectAllByDefault`` (boolean, *optional*): If specified options are selected if none was set before.

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this ViewHelper

* ``translate`` (array, *optional*): Configures translation of ViewHelper output.

* ``prependOptionLabel`` (string, *optional*): If specified, will provide an option at first position with the specified label.

* ``prependOptionValue`` (string, *optional*): If specified, will provide an option at first position with the specified value. This argument is only respected if prependOptionLabel is set.




Examples
********

**Basic usage**::

	<f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />


Expected result::

	<select name="paymentOptions">
	  <option value="payPal">PayPal International Services</option>
	  <option value="visa">VISA Card</option>
	</select>


**Preselect a default value**::

	<f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="visa" />


Expected result::

	(Generates a dropdown box like above, except that "VISA Card" is selected.)


**Use with domain objects**::

	<f:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />


Expected result::

	(Generates a dropdown box, using ids and first names of the User instances.)


**Prepend a fixed option**::

	<f:form.select property="salutation" options="{salutations}" prependOptionLabel="- select one -" />


Expected result::

	<select name="salutation">
	  <option value="">- select one -</option>
	  <option value="Mr">Mr</option>
	  <option value="Mrs">Mrs</option>
	  <option value="Ms">Ms</option>
	</select>
	(depending on variable "salutations")


**Label translation**::

	<f:form.select name="paymentOption" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" translate="{by: 'id'}" />


Expected result::

	(Generates a dropdown box and uses the values "payPal" and "visa" to look up
	translations for those ids in the current package's "Main" XLIFF file.)


**Label translation usign a prefix**::

	<f:form.select name="paymentOption" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" translate="{by: 'id', prefix: 'shop.paymentOptions.'}" />


Expected result::

	(Generates a dropdown box and uses the values "shop.paymentOptions.payPal"
	and "shop.paymentOptions.visa" to look up translations for those ids in the
	current package's "Main" XLIFF file.)




.. _`FluidAdaptor ViewHelper Reference: f:form.submit`:

f:form.submit
-------------

Creates a submit button.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\SubmitViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Defaults**::

	<f:form.submit value="Send Mail" />


Expected result::

	<input type="submit" />


**Dummy content for template preview**::

	<f:form.submit name="mySubmit" value="Send Mail"><button>dummy button</button></f:form.submit>


Expected result::

	<input type="submit" name="mySubmit" value="Send Mail" />




.. _`FluidAdaptor ViewHelper Reference: f:form.textarea`:

f:form.textarea
---------------

Textarea view helper.
The value of the text area needs to be set via the "value" attribute, as with all other form ViewHelpers.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\TextareaViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``rows`` (int, *optional*): The number of rows of a text area

* ``cols`` (int, *optional*): The number of columns of a text area

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``placeholder`` (string, *optional*): The placeholder of the textarea

* ``autofocus`` (string, *optional*): Specifies that a text area should automatically get focus when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``required`` (boolean, *optional*): If the field should be marked as required or not

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.textarea name="myTextArea" value="This is shown inside the textarea" />


Expected result::

	<textarea name="myTextArea">This is shown inside the textarea</textarea>




.. _`FluidAdaptor ViewHelper Reference: f:form.textfield`:

f:form.textfield
----------------

View Helper which creates a text field (<input type="text">).

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\TextfieldViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``required`` (boolean, *optional*): If the field is required or not

* ``type`` (string, *optional*): The field type, e.g. "text", "email", "url" etc.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``maxlength`` (int, *optional*): The maxlength attribute of the input field (will not be validated)

* ``readonly`` (string, *optional*): The readonly attribute of the input field

* ``size`` (int, *optional*): The size of the input field

* ``placeholder`` (string, *optional*): The placeholder of the input field

* ``autofocus`` (string, *optional*): Specifies that a input field should automatically get focus when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.textfield name="myTextBox" value="default value" />


Expected result::

	<input type="text" name="myTextBox" value="default value" />




.. _`FluidAdaptor ViewHelper Reference: f:form.upload`:

f:form.upload
-------------

A view helper which generates an <input type="file"> HTML element.
Make sure to set enctype="multipart/form-data" on the form!

If a file has been uploaded successfully and the form is re-displayed due to validation errors,
this ViewHelper will render hidden fields that contain the previously generated resource so you
won't have to upload the file again.

You can use a separate ViewHelper to display previously uploaded resources in order to remove/replace them.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\UploadViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``collection`` (string, *optional*): Name of the resource collection this file should be uploaded to

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.upload name="file" />


Expected result::

	<input type="file" name="file" />


**Multiple Uploads**::

	<f:form.upload property="attachments.0.originalResource" />
	<f:form.upload property="attachments.1.originalResource" />


Expected result::

	<input type="file" name="formObject[attachments][0][originalResource]">
	<input type="file" name="formObject[attachments][0][originalResource]">


**Default resource**::

	<f:form.upload name="file" value="{someDefaultResource}" />


Expected result::

	<input type="hidden" name="file[originallySubmittedResource][__identity]" value="<someDefaultResource-UUID>" />
	<input type="file" name="file" />


**Specifying the resource collection for the new resource**::

	<f:form.upload name="file" collection="invoices"/>


Expected result::

	<input type="file" name="yourInvoice" />
	<input type="hidden" name="yourInvoice[__collectionName]" value="invoices" />




.. _`FluidAdaptor ViewHelper Reference: f:form.validationResults`:

f:form.validationResults
------------------------



:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Form\\ValidationResultsViewHelper




Arguments
*********

* ``for`` (string, *optional*): The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.

* ``as`` (string, *optional*): The name of the variable to store the current error




.. _`FluidAdaptor ViewHelper Reference: f:format.base64Decode`:

f:format.base64Decode
---------------------

Applies base64_decode to the input

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\Base64DecodeViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*)




.. _`FluidAdaptor ViewHelper Reference: f:format.bytes`:

f:format.bytes
--------------

Formats an integer with a byte count into human-readable form.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\BytesViewHelper




Arguments
*********

* ``value`` (integer, *optional*): The incoming data to convert, or NULL if VH children should be used

* ``decimals`` (integer, *optional*): The number of digits after the decimal point

* ``decimalSeparator`` (string, *optional*): The decimal point character

* ``thousandsSeparator`` (string, *optional*): The character for grouping the thousand digits




Examples
********

**Defaults**::

	{fileSize -> f:format.bytes()}


Expected result::

	123 KB
	// depending on the value of {fileSize}


**Defaults**::

	{fileSize -> f:format.bytes(decimals: 2, decimalSeparator: ',', thousandsSeparator: ',')}


Expected result::

	1,023.00 B
	// depending on the value of {fileSize}




.. _`FluidAdaptor ViewHelper Reference: f:format.case`:

f:format.case
-------------

Modifies the case of an input string to upper- or lowercase or capitalization.
The default transformation will be uppercase as in ``mb_convert_case`` [1].

Possible modes are:

``lower``
  Transforms the input string to its lowercase representation

``upper``
  Transforms the input string to its uppercase representation

``capital``
  Transforms the input string to its first letter upper-cased, i.e. capitalization

``uncapital``
  Transforms the input string to its first letter lower-cased, i.e. uncapitalization

``capitalWords``
  Transforms the input string to each containing word being capitalized

Note that the behavior will be the same as in the appropriate PHP function ``mb_convert_case`` [1];
especially regarding locale and multibyte behavior.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\CaseViewHelper




Arguments
*********

* ``value`` (string, *optional*): The input value. If not given, the evaluated child nodes will be used

* ``mode`` (string, *optional*): The case to apply, must be one of this' CASE_* constants. Defaults to uppercase application




.. _`FluidAdaptor ViewHelper Reference: f:format.crop`:

f:format.crop
-------------

Use this view helper to crop the text between its opening and closing tags.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\CropViewHelper




Arguments
*********

* ``maxCharacters`` (integer): Place where to truncate the string

* ``append`` (string, *optional*): What to append, if truncation happened

* ``value`` (string, *optional*): The input value which should be cropped. If not set, the evaluated contents of the child nodes will be used




Examples
********

**Defaults**::

	<f:format.crop maxCharacters="10">This is some very long text</f:format.crop>


Expected result::

	This is so...


**Custom suffix**::

	<f:format.crop maxCharacters="17" append=" [more]">This is some very long text</f:format.crop>


Expected result::

	This is some very [more]


**Inline notation**::

	<span title="Location: {user.city -> f:format.crop(maxCharacters: '12')}">John Doe</span>


Expected result::

	<span title="Location: Newtownmount...">John Doe</span>




.. _`FluidAdaptor ViewHelper Reference: f:format.currency`:

f:format.currency
-----------------

Formats a given float to a currency representation.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\CurrencyViewHelper




Arguments
*********

* ``forceLocale`` (mixed, *optional*): Whether if, and what, Locale should be used. May be boolean, string or \Neos\Flow\I18n\Locale

* ``currencySign`` (string, *optional*): (optional) The currency sign, eg $ or €.

* ``decimalSeparator`` (string, *optional*): (optional) The separator for the decimal point.

* ``thousandsSeparator`` (string, *optional*): (optional) The thousands separator.

* ``prependCurrency`` (boolean, *optional*): (optional) Indicates if currency symbol should be placed before or after the numeric value.

* ``separateCurrency`` (boolean, *optional*): (optional) Indicates if a space character should be placed between the number and the currency sign.

* ``decimals`` (integer, *optional*): (optional) The number of decimal places.




Examples
********

**Defaults**::

	<f:format.currency>123.456</f:format.currency>


Expected result::

	123,46


**All parameters**::

	<f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator="," prependCurrency="false", separateCurrency="true", decimals="2">54321</f:format.currency>


Expected result::

	54,321.00 $


**Inline notation**::

	{someNumber -> f:format.currency(thousandsSeparator: ',', currencySign: '€')}


Expected result::

	54,321,00 €
	(depending on the value of {someNumber})


**Inline notation with current locale used**::

	{someNumber -> f:format.currency(currencySign: '€', forceLocale: true)}


Expected result::

	54.321,00 €
	(depending on the value of {someNumber} and the current locale)


**Inline notation with specific locale used**::

	{someNumber -> f:format.currency(currencySign: 'EUR', forceLocale: 'de_DE')}


Expected result::

	54.321,00 EUR
	(depending on the value of {someNumber})


**Inline notation with different position for the currency sign**::

	{someNumber -> f:format.currency(currencySign: '€', prependCurrency: 'true')}


Expected result::

	€ 54.321,00
	(depending on the value of {someNumber})


**Inline notation with no space between the currency and no decimal places**::

	{someNumber -> f:format.currency(currencySign: '€', separateCurrency: 'false', decimals: '0')}


Expected result::

	54.321€
	(depending on the value of {someNumber})




.. _`FluidAdaptor ViewHelper Reference: f:format.date`:

f:format.date
-------------

Formats a \DateTime object.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\DateViewHelper




Arguments
*********

* ``forceLocale`` (mixed, *optional*): Whether if, and what, Locale should be used. May be boolean, string or \Neos\Flow\I18n\Locale

* ``date`` (mixed, *optional*): either a \DateTime object or a string that is accepted by \DateTime constructor

* ``format`` (string, *optional*): Format String which is taken to format the Date/Time if none of the locale options are set.

* ``localeFormatType`` (string, *optional*): Whether to format (according to locale set in $forceLocale) date, time or datetime. Must be one of Neos\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_*'s constants.

* ``localeFormatLength`` (string, *optional*): Format length if locale set in $forceLocale. Must be one of Neos\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_*'s constants.

* ``cldrFormat`` (string, *optional*): Format string in CLDR format (see http://cldr.unicode.org/translation/date-time)




Examples
********

**Defaults**::

	<f:format.date>{dateObject}</f:format.date>


Expected result::

	1980-12-13
	(depending on the current date)


**Custom date format**::

	<f:format.date format="H:i">{dateObject}</f:format.date>


Expected result::

	01:23
	(depending on the current time)


**strtotime string**::

	<f:format.date format="d.m.Y - H:i:s">+1 week 2 days 4 hours 2 seconds</f:format.date>


Expected result::

	13.12.1980 - 21:03:42
	(depending on the current time, see http://www.php.net/manual/en/function.strtotime.php)


**output date from unix timestamp**::

	<f:format.date format="d.m.Y - H:i:s">@{someTimestamp}</f:format.date>


Expected result::

	13.12.1980 - 21:03:42
	(depending on the current time. Don't forget the "@" in front of the timestamp see http://www.php.net/manual/en/function.strtotime.php)


**Inline notation**::

	{f:format.date(date: dateObject)}


Expected result::

	1980-12-13
	(depending on the value of {dateObject})


**Inline notation (2nd variant)**::

	{dateObject -> f:format.date()}


Expected result::

	1980-12-13
	(depending on the value of {dateObject})


**Inline notation, outputting date only, using current locale**::

	{dateObject -> f:format.date(localeFormatType: 'date', forceLocale: true)}


Expected result::

	13.12.1980
	(depending on the value of {dateObject} and the current locale)


**Inline notation with specific locale used**::

	{dateObject -> f:format.date(forceLocale: 'de_DE')}


Expected result::

	13.12.1980 11:15:42
	(depending on the value of {dateObject})




.. _`FluidAdaptor ViewHelper Reference: f:format.htmlentities`:

f:format.htmlentities
---------------------

Applies htmlentities() escaping to a value

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\HtmlentitiesViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*)

* ``doubleEncode`` (boolean, *optional*): If FALSE existing html entities won't be encoded, the default is to convert everything.




.. _`FluidAdaptor ViewHelper Reference: f:format.htmlentitiesDecode`:

f:format.htmlentitiesDecode
---------------------------

Applies html_entity_decode() to a value

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\HtmlentitiesDecodeViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*)




.. _`FluidAdaptor ViewHelper Reference: f:format.identifier`:

f:format.identifier
-------------------

This ViewHelper renders the identifier of a persisted object (if it has an identity).
Usually the identifier is the UUID of the object, but it could be an array of the
identity properties, too.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\IdentifierViewHelper




Arguments
*********

* ``value`` (object, *optional*): the object to render the identifier for, or NULL if VH children should be used




.. _`FluidAdaptor ViewHelper Reference: f:format.json`:

f:format.json
-------------

Wrapper for PHPs json_encode function.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\JsonViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The incoming data to convert, or NULL if VH children should be used

* ``forceObject`` (boolean, *optional*): Outputs an JSON object rather than an array




Examples
********

**encoding a view variable**::

	{someArray -> f:format.json()}


Expected result::

	["array","values"]
	// depending on the value of {someArray}


**associative array**::

	{f:format.json(value: {foo: 'bar', bar: 'baz'})}


Expected result::

	{"foo":"bar","bar":"baz"}


**non-associative array with forced object**::

	{f:format.json(value: {0: 'bar', 1: 'baz'}, forceObject: true)}


Expected result::

	{"0":"bar","1":"baz"}




.. _`FluidAdaptor ViewHelper Reference: f:format.nl2br`:

f:format.nl2br
--------------

Wrapper for PHPs nl2br function.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\Nl2brViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format




.. _`FluidAdaptor ViewHelper Reference: f:format.number`:

f:format.number
---------------

Formats a number with custom precision, decimal point and grouped thousands.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\NumberViewHelper




Arguments
*********

* ``forceLocale`` (mixed, *optional*): Whether if, and what, Locale should be used. May be boolean, string or \Neos\Flow\I18n\Locale

* ``decimals`` (integer, *optional*): The number of digits after the decimal point

* ``decimalSeparator`` (string, *optional*): The decimal point character

* ``thousandsSeparator`` (string, *optional*): The character for grouping the thousand digits

* ``localeFormatLength`` (string, *optional*): Format length if locale set in $forceLocale. Must be one of Neos\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_*'s constants.




.. _`FluidAdaptor ViewHelper Reference: f:format.padding`:

f:format.padding
----------------

Formats a string using PHPs str_pad function.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\PaddingViewHelper




Arguments
*********

* ``padLength`` (integer): Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.

* ``padString`` (string, *optional*): The padding string

* ``padType`` (string, *optional*): Append the padding at this site (Possible values: right,left,both. Default: right)

* ``value`` (string, *optional*): string to format




.. _`FluidAdaptor ViewHelper Reference: f:format.stripTags`:

f:format.stripTags
------------------

Removes tags from the given string (applying PHPs strip_tags() function)

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\StripTagsViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format




.. _`FluidAdaptor ViewHelper Reference: f:format.urlencode`:

f:format.urlencode
------------------

Encodes the given string according to http://www.faqs.org/rfcs/rfc3986.html (applying PHPs rawurlencode() function)

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Format\\UrlencodeViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format




.. _`FluidAdaptor ViewHelper Reference: f:link.action`:

f:link.action
-------------

A view helper for creating links to actions.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Link\\ActionViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``action`` (string): Target action

* ``arguments`` (array, *optional*): Arguments

* ``controller`` (string, *optional*): Target controller. If NULL current controllerName is used

* ``package`` (string, *optional*): Target package. if NULL current package is used

* ``subpackage`` (string, *optional*): Target subpackage. if NULL current subpackage is used

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

* ``useParentRequest`` (boolean, *optional*): If set, the parent Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care

* ``absolute`` (boolean, *optional*): By default this ViewHelper renders links with absolute URIs. If this is FALSE, a relative URI is created instead

* ``useMainRequest`` (boolean, *optional*): If set, the main Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**Defaults**::

	<f:link.action>some link</f:link.action>


Expected result::

	<a href="currentpackage/currentcontroller">some link</a>
	(depending on routing setup and current package/controller/action)


**Additional arguments**::

	<f:link.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:link.action>


Expected result::

	<a href="mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2">some link</a>
	(depending on routing setup)




.. _`FluidAdaptor ViewHelper Reference: f:link.email`:

f:link.email
------------

Email link view helper.
Generates an email link.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Link\\EmailViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``email`` (string): The email address to be turned into a link.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**basic email link**::

	<f:link.email email="foo@bar.tld" />


Expected result::

	<a href="mailto:foo@bar.tld">foo@bar.tld</a>


**Email link with custom linktext**::

	<f:link.email email="foo@bar.tld">some custom content</f:link.email>


Expected result::

	<a href="mailto:foo@bar.tld">some custom content</a>




.. _`FluidAdaptor ViewHelper Reference: f:link.external`:

f:link.external
---------------

A view helper for creating links to external targets.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Link\\ExternalViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``uri`` (string): the URI that will be put in the href attribute of the rendered link tag

* ``defaultScheme`` (string, *optional*): scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**custom default scheme**::

	<f:link.external uri="neos.io" defaultScheme="sftp">external ftp link</f:link.external>


Expected result::

	<a href="sftp://neos.io">external ftp link</a>




.. _`FluidAdaptor ViewHelper Reference: f:renderChildren`:

f:renderChildren
----------------

Render the inner parts of a Widget.
This ViewHelper can only be used in a template which belongs to a Widget Controller.

It renders everything inside the Widget ViewHelper, and you can pass additional
arguments.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\RenderChildrenViewHelper




Arguments
*********

* ``arguments`` (array, *optional*)




Examples
********

**Basic usage**::

	<!-- in the widget template -->
	Header
	<f:renderChildren arguments="{foo: 'bar'}" />
	Footer
	
	<-- in the outer template, using the widget -->
	
	<x:widget.someWidget>
	  Foo: {foo}
	</x:widget.someWidget>


Expected result::

	Header
	Foo: bar
	Footer




.. _`FluidAdaptor ViewHelper Reference: f:security.csrfToken`:

f:security.csrfToken
--------------------

ViewHelper that outputs a CSRF token which is required for "unsafe" requests (e.g. POST, PUT, DELETE, ...).

Note: You won't need this ViewHelper if you use the Form ViewHelper, because that creates a hidden field with
the CSRF token for unsafe requests automatically. This ViewHelper is mainly useful in conjunction with AJAX.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Security\\CsrfTokenViewHelper





.. _`FluidAdaptor ViewHelper Reference: f:security.ifAccess`:

f:security.ifAccess
-------------------

This view helper implements an ifAccess/else condition.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Security\\IfAccessViewHelper




Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``privilegeTarget`` (string): Condition expression conforming to Fluid boolean rules

* ``parameters`` (array, *optional*): Condition expression conforming to Fluid boolean rules




.. _`FluidAdaptor ViewHelper Reference: f:security.ifAuthenticated`:

f:security.ifAuthenticated
--------------------------

This view helper implements an ifAuthenticated/else condition.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Security\\IfAuthenticatedViewHelper




Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``condition`` (boolean, *optional*): Condition expression conforming to Fluid boolean rules




.. _`FluidAdaptor ViewHelper Reference: f:security.ifHasRole`:

f:security.ifHasRole
--------------------

This view helper implements an ifHasRole/else condition.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Security\\IfHasRoleViewHelper




Arguments
*********

* ``role`` (mixed): The role or role identifier.

* ``packageKey`` (string, *optional*): PackageKey of the package defining the role.

* ``account`` (Neos\Flow\Security\Account, *optional*): If specified, this subject of this check is the given Account instead of the currently authenticated account

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.




.. _`FluidAdaptor ViewHelper Reference: f:translate`:

f:translate
-----------

Returns translated message using source message or key ID.

Also replaces all placeholders with formatted versions of provided values.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\TranslateViewHelper




Arguments
*********

* ``id`` (string, *optional*): Id to use for finding translation (trans-unit id in XLIFF)

* ``value`` (string, *optional*): If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default

* ``arguments`` (array, *optional*): Numerically indexed array of values to be inserted into placeholders

* ``source`` (string, *optional*): Name of file with translations (use / as a directory separator)

* ``package`` (string, *optional*): Target package key. If not set, the current package key will be used

* ``quantity`` (mixed, *optional*): A number to find plural form for (float or int), NULL to not use plural forms

* ``locale`` (string, *optional*): An identifier of locale to use (NULL for use the default locale)




Examples
********

**Translation by id**::

	<f:translate id="user.unregistered">Unregistered User</f:translate>


Expected result::

	translation of label with the id "user.unregistered" and a fallback to "Unregistered User"


**Inline notation**::

	{f:translate(id: 'some.label.id', value: 'fallback result')}


Expected result::

	translation of label with the id "some.label.id" and a fallback to "fallback result"


**Custom source and locale**::

	<f:translate id="some.label.id" source="LabelsCatalog" locale="de_DE"/>


Expected result::

	translation from custom source "SomeLabelsCatalog" for locale "de_DE"


**Custom source from other package**::

	<f:translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>


Expected result::

	translation from custom source "LabelsCatalog" in "OtherPackage"


**Arguments**::

	<f:translate arguments="{0: 'foo', 1: '99.9'}"><![CDATA[Untranslated {0} and {1,number}]]></f:translate>


Expected result::

	translation of the label "Untranslated foo and 99.9"


**Translation by label**::

	<f:translate>Untranslated label</f:translate>


Expected result::

	translation of the label "Untranslated label"




.. _`FluidAdaptor ViewHelper Reference: f:uri.action`:

f:uri.action
------------

A view helper for creating URIs to actions.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Uri\\ActionViewHelper




Arguments
*********

* ``action`` (string): Target action

* ``arguments`` (array, *optional*): Arguments

* ``controller`` (string, *optional*): Target controller. If NULL current controllerName is used

* ``package`` (string, *optional*): Target package. if NULL current package is used

* ``subpackage`` (string, *optional*): Target subpackage. if NULL current subpackage is used

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

* ``useParentRequest`` (boolean, *optional*): If set, the parent Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care

* ``useMainRequest`` (boolean, *optional*): If set, the main Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care




Examples
********

**Defaults**::

	<f:uri.action>some link</f:uri.action>


Expected result::

	currentpackage/currentcontroller
	(depending on routing setup and current package/controller/action)


**Additional arguments**::

	<f:uri.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:uri.action>


Expected result::

	mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2
	(depending on routing setup)




.. _`FluidAdaptor ViewHelper Reference: f:uri.email`:

f:uri.email
-----------

Email uri view helper.
Currently the specified email is simply prepended by "mailto:" but we might add spam protection.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Uri\\EmailViewHelper




Arguments
*********

* ``email`` (string): The email address to be turned into a mailto uri.




Examples
********

**basic email uri**::

	<f:uri.email email="foo@bar.tld" />


Expected result::

	mailto:foo@bar.tld




.. _`FluidAdaptor ViewHelper Reference: f:uri.external`:

f:uri.external
--------------

A view helper for creating URIs to external targets.
Currently the specified URI is simply passed through.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Uri\\ExternalViewHelper




Arguments
*********

* ``uri`` (string): target URI

* ``defaultScheme`` (string, *optional*): scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already




Examples
********

**custom default scheme**::

	<f:uri.external uri="neos.io" defaultScheme="sftp" />


Expected result::

	sftp://neos.io




.. _`FluidAdaptor ViewHelper Reference: f:uri.resource`:

f:uri.resource
--------------

A view helper for creating URIs to resources.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Uri\\ResourceViewHelper




Arguments
*********

* ``path`` (string, *optional*): The location of the resource, can be either a path relative to the Public resource directory of the package or a resource://... URI

* ``package`` (string, *optional*): Target package key. If not set, the current package key will be used

* ``resource`` (Neos\Flow\ResourceManagement\PersistentResource, *optional*): If specified, this resource object is used instead of the path and package information

* ``localize`` (boolean, *optional*): Whether resource localization should be attempted or not




Examples
********

**Defaults**::

	<link href="{f:uri.resource(path: 'CSS/Stylesheet.css')}" rel="stylesheet" />


Expected result::

	<link href="http://yourdomain.tld/_Resources/Static/YourPackage/CSS/Stylesheet.css" rel="stylesheet" />
	(depending on current package)


**Other package resource**::

	{f:uri.resource(path: 'gfx/SomeImage.png', package: 'DifferentPackage')}


Expected result::

	http://yourdomain.tld/_Resources/Static/DifferentPackage/gfx/SomeImage.png
	(depending on domain)


**Static resource URI**::

	{f:uri.resource(path: 'resource://DifferentPackage/Public/gfx/SomeImage.png')}


Expected result::

	http://yourdomain.tld/_Resources/Static/DifferentPackage/gfx/SomeImage.png
	(depending on domain)


**Persistent resource object**::

	<img src="{f:uri.resource(resource: myImage.resource)}" />


Expected result::

	<img src="http://yourdomain.tld/_Resources/Persistent/69e73da3ce0ad08c717b7b9f1c759182d6650944.jpg" />
	(depending on your resource object)




.. _`FluidAdaptor ViewHelper Reference: f:validation.ifHasErrors`:

f:validation.ifHasErrors
------------------------

This view helper allows to check whether validation errors adhere to the current request.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Validation\\IfHasErrorsViewHelper




Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``for`` (string, *optional*): The argument or property name or path to check for error(s). If not set any validation error leads to the "then child" to be rendered




.. _`FluidAdaptor ViewHelper Reference: f:validation.results`:

f:validation.results
--------------------

Validation results view helper

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Validation\\ResultsViewHelper




Arguments
*********

* ``for`` (string, *optional*): The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.

* ``as`` (string, *optional*): The name of the variable to store the current error




Examples
********

**Output error messages as a list**::

	<f:validation.results>
	  <f:if condition="{validationResults.flattenedErrors}">
	    <ul class="errors">
	      <f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
	        <li>{propertyPath}
	          <ul>
	          <f:for each="{errors}" as="error">
	            <li>{error.code}: {error}</li>
	          </f:for>
	          </ul>
	        </li>
	      </f:for>
	    </ul>
	  </f:if>
	</f:validation.results>


Expected result::

	<ul class="errors">
	  <li>1234567890: Validation errors for argument "newBlog"</li>
	</ul>


**Output error messages for a single property**::

	<f:validation.results for="someProperty">
	  <f:if condition="{validationResults.flattenedErrors}">
	    <ul class="errors">
	      <f:for each="{validationResults.errors}" as="error">
	        <li>{error.code}: {error}</li>
	      </f:for>
	    </ul>
	  </f:if>
	</f:validation.results>


Expected result::

	<ul class="errors">
	  <li>1234567890: Some error message</li>
	</ul>




.. _`FluidAdaptor ViewHelper Reference: f:widget.autocomplete`:

f:widget.autocomplete
---------------------

Usage:
<f:input id="name" ... />
<f:widget.autocomplete for="name" objects="{posts}" searchProperty="author">

Make sure to include jQuery and jQuery UI in the HTML, like that:
   <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
   <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
   <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/themes/base/jquery-ui.css" type="text/css" media="all" />
   <link rel="stylesheet" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" type="text/css" media="all" />

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Widget\\AutocompleteViewHelper




Arguments
*********

* ``objects`` (Neos\Flow\Persistence\QueryResultInterface)

* ``for`` (string)

* ``searchProperty`` (string)

* ``configuration`` (array, *optional*)

* ``widgetId`` (string, *optional*): Unique identifier of the widget instance




.. _`FluidAdaptor ViewHelper Reference: f:widget.link`:

f:widget.link
-------------

widget.link ViewHelper
This ViewHelper can be used inside widget templates in order to render links pointing to widget actions

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Widget\\LinkViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




.. _`FluidAdaptor ViewHelper Reference: f:widget.paginate`:

f:widget.paginate
-----------------

This ViewHelper renders a Pagination of objects.

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Widget\\PaginateViewHelper




Arguments
*********

* ``objects`` (Neos\Flow\Persistence\QueryResultInterface)

* ``as`` (string)

* ``configuration`` (array, *optional*)

* ``widgetId`` (string, *optional*): Unique identifier of the widget instance




.. _`FluidAdaptor ViewHelper Reference: f:widget.uri`:

f:widget.uri
------------

widget.uri ViewHelper
This ViewHelper can be used inside widget templates in order to render URIs pointing to widget actions

:Implementation: Neos\\FluidAdaptor\\ViewHelpers\\Widget\\UriViewHelper




Arguments
*********

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)



