.. _`Neos ViewHelper Reference`:

Neos ViewHelper Reference
=========================

This reference was automatically generated from code on 2016-06-07


.. _`Neos ViewHelper Reference: neos:backend.authenticationProviderLabel`:

neos:backend.authenticationProviderLabel
----------------------------------------

Renders a label for the given authentication provider identifier

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\AuthenticationProviderLabelViewHelper




Arguments
*********

* ``identifier`` (string)




.. _`Neos ViewHelper Reference: neos:backend.changeStats`:

neos:backend.changeStats
------------------------

Displays a text-based "bar graph" giving an indication of the amount and type of
changes done to something. Created for use in workspace management.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ChangeStatsViewHelper




Arguments
*********

* ``changeCounts`` (array): Expected keys: new, changed, removed




.. _`Neos ViewHelper Reference: neos:backend.colorOfString`:

neos:backend.colorOfString
--------------------------

Generates a color code for a given string

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ColorOfStringViewHelper




Arguments
*********

* ``string`` (string, *optional*)

* ``minimalBrightness`` (integer, *optional*)




.. _`Neos ViewHelper Reference: neos:backend.configurationCacheVersion`:

neos:backend.configurationCacheVersion
--------------------------------------

ViewHelper for rendering the current version identifier for the
configuration cache.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ConfigurationCacheVersionViewHelper





.. _`Neos ViewHelper Reference: neos:backend.configurationTree`:

neos:backend.configurationTree
------------------------------

Render HTML markup for the full configuration tree in the Neos Administration -> Configuration Module.

For performance reasons, this is done inside a ViewHelper instead of Fluid itself.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ConfigurationTreeViewHelper




Arguments
*********

* ``configuration`` (array)




.. _`Neos ViewHelper Reference: neos:backend.container`:

neos:backend.container
----------------------

ViewHelper for the backend 'container'. Renders the required HTML to integrate
the Neos backend into a website.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ContainerViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface)




.. _`Neos ViewHelper Reference: neos:backend.cssBuiltVersion`:

neos:backend.cssBuiltVersion
----------------------------

Returns a shortened md5 of the built CSS file

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\CssBuiltVersionViewHelper





.. _`Neos ViewHelper Reference: neos:backend.documentBreadcrumbPath`:

neos:backend.documentBreadcrumbPath
-----------------------------------

Render a bread crumb path by using the labels of documents leading to the given node path

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\DocumentBreadcrumbPathViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface): A node




.. _`Neos ViewHelper Reference: neos:backend.interfaceLanguage`:

neos:backend.interfaceLanguage
------------------------------

ViewHelper for rendering the current backend users interface language.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\InterfaceLanguageViewHelper





.. _`Neos ViewHelper Reference: neos:backend.javascriptBuiltVersion`:

neos:backend.javascriptBuiltVersion
-----------------------------------

Returns a shortened md5 of the built JavaScript file

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\JavascriptBuiltVersionViewHelper





.. _`Neos ViewHelper Reference: neos:backend.javascriptConfiguration`:

neos:backend.javascriptConfiguration
------------------------------------

ViewHelper for the backend JavaScript configuration. Renders the required JS snippet to configure
the Neos backend.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\JavascriptConfigurationViewHelper





.. _`Neos ViewHelper Reference: neos:backend.shouldLoadMinifiedJavascript`:

neos:backend.shouldLoadMinifiedJavascript
-----------------------------------------

Returns TRUE if the minified Neos JavaScript sources should be loaded, FALSE otherwise.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\ShouldLoadMinifiedJavascriptViewHelper





.. _`Neos ViewHelper Reference: neos:backend.translate`:

neos:backend.translate
----------------------

Returns translated message using source message or key ID.
uses the selected backend language
* Also replaces all placeholders with formatted versions of provided values.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\TranslateViewHelper




Arguments
*********

* ``id`` (string, *optional*): Id to use for finding translation (trans-unit id in XLIFF)

* ``value`` (string, *optional*): If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default

* ``arguments`` (array, *optional*): Numerically indexed array of values to be inserted into placeholders

* ``source`` (string, *optional*): Name of file with translations

* ``package`` (string, *optional*): Target package key. If not set, the current package key will be used

* ``quantity`` (mixed, *optional*): A number to find plural form for (float or int), NULL to not use plural forms

* ``languageIdentifier`` (string, *optional*): An identifier of a language to use (NULL for using the default language)




Examples
********

**Translation by id**::

	<neos:backend.translate id="user.unregistered">Unregistered User</neos:backend.translate>


Expected result::

	translation of label with the id "user.unregistered" and a fallback to "Unregistered User"


**Inline notation**::

	{neos:backend.translate(id: 'some.label.id', value: 'fallback result')}


Expected result::

	translation of label with the id "some.label.id" and a fallback to "fallback result"


**Custom source and locale**::

	<neos:backend.translate id="some.label.id" source="SomeLabelsCatalog" locale="de_DE"/>


Expected result::

	translation from custom source "SomeLabelsCatalog" for locale "de_DE"


**Custom source from other package**::

	<neos:backend.translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>


Expected result::

	translation from custom source "LabelsCatalog" in "OtherPackage"


**Arguments**::

	<neos:backend.translate arguments="{0: 'foo', 1: '99.9'}"><![CDATA[Untranslated {0} and {1,number}]]></neos:backend.translate>


Expected result::

	translation of the label "Untranslated foo and 99.9"


**Translation by label**::

	<neos:backend.translate>Untranslated label</neos:backend.translate>


Expected result::

	translation of the label "Untranslated label"




.. _`Neos ViewHelper Reference: neos:backend.userInitials`:

neos:backend.userInitials
-------------------------

Render user initials for a given username

This ViewHelper is *WORK IN PROGRESS* and *NOT STABLE YET*

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\UserInitialsViewHelper




Arguments
*********

* ``format`` (string, *optional*): Supported are "fullFirstName" and "initials




.. _`Neos ViewHelper Reference: neos:backend.xliffCacheVersion`:

neos:backend.xliffCacheVersion
------------------------------

ViewHelper for rendering the current version identifier for the
xliff cache.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Backend\\XliffCacheVersionViewHelper





.. _`Neos ViewHelper Reference: neos:contentElement.editable`:

neos:contentElement.editable
----------------------------

Renders a wrapper around the inner contents of the tag to enable frontend editing.

The wrapper contains the property name which should be made editable, and is by default
a "div" tag. The tag to use can be given as `tag` argument to the ViewHelper.

In live workspace this just renders a tag with the specified $tag-name containing the value of the given $property.
For logged in users with access to the Backend this also adds required attributes for the RTE to work.

Note: when passing a node you have to make sure a metadata wrapper is used around this that matches the given node
(see contentElement.wrap - i.e. the WrapViewHelper).

:Implementation: TYPO3\\Neos\\ViewHelpers\\ContentElement\\EditableViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``property`` (string): Name of the property to render. Note: If this tag has child nodes, they overrule this argument!

* ``tag`` (string, *optional*): The name of the tag that should be wrapped around the property. By default this is a <div>

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*): The node of the content element. Optional, will be resolved from the TypoScript context by default.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




.. _`Neos ViewHelper Reference: neos:contentElement.wrap`:

neos:contentElement.wrap
------------------------

A view helper for manually wrapping content editables.

Note that using this view helper is usually not necessary as Neos will automatically wrap editables of content
elements.

By explicitly wrapping template parts with node meta data that is required for the backend to show properties in the
inspector, this ViewHelper enables usage of the ``contentElement.editable`` ViewHelper outside of content element
templates. This is useful if you want to make properties of a custom document node inline-editable.

:Implementation: TYPO3\\Neos\\ViewHelpers\\ContentElement\\WrapViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*): The node of the content element. Optional, will be resolved from the TypoScript context by default.




.. _`Neos ViewHelper Reference: neos:getType`:

neos:getType
------------

View helper to check if a given value is an array.

:Implementation: TYPO3\\Neos\\ViewHelpers\\GetTypeViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The value to determine the type of




Examples
********

**Basic usage**::

	{neos:getType(value: 'foo')}


Expected result::

	string


**Use with shorthand syntax**::

	{myValue -> neos:getType()}


Expected result::

	string
	(if myValue is a string)




.. _`Neos ViewHelper Reference: neos:includeJavaScript`:

neos:includeJavaScript
----------------------

A View Helper to include JavaScript files inside Resources/Public/JavaScript of the package.

:Implementation: TYPO3\\Neos\\ViewHelpers\\IncludeJavaScriptViewHelper




Arguments
*********

* ``include`` (string): Regular expression of files to include

* ``exclude`` (string, *optional*): Regular expression of files to exclude

* ``package`` (string, *optional*): The package key of the resources to include or current controller package if NULL

* ``subpackage`` (string, *optional*): The subpackage key of the resources to include or current controller subpackage if NULL

* ``directory`` (string, *optional*): The directory inside the current subpackage. By default, the "JavaScript" directory will be used.




.. _`Neos ViewHelper Reference: neos:link.module`:

neos:link.module
----------------

A view helper for creating links to modules.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Link\\ModuleViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``path`` (string): Target module path

* ``action`` (string, *optional*): Target module action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

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

	<neos:link.module path="system/useradmin">some link</neos:link.module>


Expected result::

	<a href="neos/system/useradmin">some link</a>




.. _`Neos ViewHelper Reference: neos:link.node`:

neos:link.node
--------------

A view helper for creating links with URIs pointing to nodes.

The target node can be provided as string or as a Node object; if not specified
at all, the generated URI will refer to the current document node inside the TypoScript context.

When specifying the ``node`` argument as string, the following conventions apply:

*``node`` starts with ``/``:*
The given path is an absolute node path and is treated as such.
Example: ``/sites/acmecom/home/about/us``

*``node`` does not start with ``/``:*
The given path is treated as a path relative to the current node.
Examples: given that the current node is ``/sites/acmecom/products/``,
``stapler`` results in ``/sites/acmecom/products/stapler``,
``../about`` results in ``/sites/acmecom/about/``,
``./neos/info`` results in ``/sites/acmecom/products/neos/info``.

*``node`` starts with a tilde character (``~``):*
The given path is treated as a path relative to the current site node.
Example: given that the current node is ``/sites/acmecom/products/``,
``~/about/us`` results in ``/sites/acmecom/about/us``,
``~`` results in ``/sites/acmecom``.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Link\\NodeViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``node`` (mixed, *optional*): A node object or a string node path or NULL to resolve the current document node

* ``format`` (string, *optional*): Format to use for the URL, for example "html" or "json

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``arguments`` (array, *optional*): Additional arguments to be passed to the UriBuilder (for example pagination parameters)

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

* ``baseNodeName`` (string, *optional*): The variable the node will be assigned to for the rendered child content

* ``nodeVariableName`` (string, *optional*): The name of the base node inside the TypoScript context to use for the ContentContext or resolving relative paths

* ``resolveShortcuts`` (boolean, *optional*): INTERNAL Parameter - if FALSE, shortcuts are not redirected to their target. Only needed on rare backend occasions when we want to link to the shortcut itself.

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

	<neos:link.node>some link</neos:link.node>


Expected result::

	<a href="sites/mysite.com/homepage/about.html">some link</a>
	(depending on current node, format etc.)


**Generating a link with an absolute URI**::

	<neos:link.node absolute="{true}">bookmark this page</neos:link.node>


Expected result::

	<a href="http://www.example.org/homepage/about.html">bookmark this page</a>
	(depending on current workspace, current node, format, host etc.)


**Target node given as absolute node path**::

	<neos:link.node node="/sites/exampleorg/contact/imprint">Corporate imprint</neos:link.node>


Expected result::

	<a href="contact/imprint.html">Corporate imprint</a>
	(depending on current workspace, current node, format etc.)


**Target node given as relative node path**::

	<neos:link.node node="~/about/us">About us</neos:link.node>


Expected result::

	<a href="about/us.html">About us</a>
	(depending on current workspace, current node, format etc.)


**Node label as tag content**::

	<neos:link.node node="/sites/exampleorg/contact/imprint" />


Expected result::

	<a href="contact/imprint.html">Imprint</a>
	(depending on current workspace, current node, format etc.)


**Dynamic tag content involving the linked node's properties**::

	<neos:link.node node="about-us">see our <span>{linkedNode.label}</span> page</neos:link.node>


Expected result::

	<a href="about-us.html">see our <span>About Us</span> page</a>
	(depending on current workspace, current node, format etc.)




.. _`Neos ViewHelper Reference: neos:node.closestDocument`:

neos:node.closestDocument
-------------------------

ViewHelper to find the closest document node to a given node

:Implementation: TYPO3\\Neos\\ViewHelpers\\Node\\ClosestDocumentViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface)




.. _`Neos ViewHelper Reference: neos:rendering.inBackend`:

neos:rendering.inBackend
------------------------

ViewHelper to find out if Neos is rendering the backend.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Rendering\\InBackendViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*)




Examples
********

**Basic usage**::

	<f:if condition="{neos:rendering.inBackend()}">
	  <f:then>
	    Shown in the backend.
	  </f:then>
	  <f:else>
	    Shown when not in backend.
	  </f:else>
	</f:if>


Expected result::

	Shown in the backend.




.. _`Neos ViewHelper Reference: neos:rendering.inEditMode`:

neos:rendering.inEditMode
-------------------------

ViewHelper to find out if Neos is rendering an edit mode.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Rendering\\InEditModeViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*): Optional Node to use context from

* ``mode`` (string, *optional*): Optional rendering mode name to check if this specific mode is active




Examples
********

**Basic usage**::

	<f:if condition="{neos:rendering.inEditMode()}">
	  <f:then>
	    Shown for editing.
	  </f:then>
	  <f:else>
	    Shown elsewhere (preview mode or not in backend).
	  </f:else>
	</f:if>


Expected result::

	Shown for editing.


**Advanced usage**::

	<f:if condition="{neos:rendering.inEditMode(mode: 'rawContent')}">
	  <f:then>
	    Shown just for rawContent editing mode.
	  </f:then>
	  <f:else>
	    Shown in all other cases.
	  </f:else>
	</f:if>


Expected result::

	Shown in all other cases.




.. _`Neos ViewHelper Reference: neos:rendering.inPreviewMode`:

neos:rendering.inPreviewMode
----------------------------

ViewHelper to find out if Neos is rendering a preview mode.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Rendering\\InPreviewModeViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*): Optional Node to use context from

* ``mode`` (string, *optional*): Optional rendering mode name to check if this specific mode is active




Examples
********

**Basic usage**::

	<f:if condition="{neos:rendering.inPreviewMode()}">
	  <f:then>
	    Shown in preview.
	  </f:then>
	  <f:else>
	    Shown elsewhere (edit mode or not in backend).
	  </f:else>
	</f:if>


Expected result::

	Shown in preview.


**Advanced usage**::

	<f:if condition="{neos:rendering.inPreviewMode(mode: 'print')}">
	  <f:then>
	    Shown just for print preview mode.
	  </f:then>
	  <f:else>
	    Shown in all other cases.
	  </f:else>
	</f:if>


Expected result::

	Shown in all other cases.




.. _`Neos ViewHelper Reference: neos:rendering.live`:

neos:rendering.live
-------------------

ViewHelper to find out if Neos is rendering the live website.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Rendering\\LiveViewHelper




Arguments
*********

* ``node`` (TYPO3\TYPO3CR\Domain\Model\NodeInterface, *optional*)




Examples
********

**Basic usage**::

	<f:if condition="{neos:rendering.live()}">
	  <f:then>
	    Shown outside the backend.
	  </f:then>
	  <f:else>
	    Shown in the backend.
	  </f:else>
	</f:if>


Expected result::

	Shown in the backend.




.. _`Neos ViewHelper Reference: neos:standaloneView`:

neos:standaloneView
-------------------

A View Helper to render a fluid template based on the given template path and filename.

This will just set up a standalone Fluid view and render the template found at the
given path and filename. Any arguments passed will be assigned to that template,
the rendering result is returned.

:Implementation: TYPO3\\Neos\\ViewHelpers\\StandaloneViewViewHelper




Arguments
*********

* ``templatePathAndFilename`` (string): Path and filename of the template to render

* ``arguments`` (array, *optional*): Arguments to assign to the template before rendering




Examples
********

**Basic usage**::

	<neos:standaloneView templatePathAndFilename="fancyTemplatePathAndFilename" arguments="{foo: bar, quux: baz}" />


Expected result::

	<some><fancy/></html
	(depending on template and arguments given)




.. _`Neos ViewHelper Reference: neos:uri.module`:

neos:uri.module
---------------

A view helper for creating links to modules.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Uri\\ModuleViewHelper




Arguments
*********

* ``path`` (string): Target module path

* ``action`` (string, *optional*): Target module action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE




Examples
********

**Defaults**::

	<link rel="some-module" href="{neos:link.module(path: 'system/useradmin')}" />


Expected result::

	<link rel="some-module" href="neos/system/useradmin" />




.. _`Neos ViewHelper Reference: neos:uri.node`:

neos:uri.node
-------------

A view helper for creating URIs pointing to nodes.

The target node can be provided as string or as a Node object; if not specified
at all, the generated URI will refer to the current document node inside the TypoScript context.

When specifying the ``node`` argument as string, the following conventions apply:

*``node`` starts with ``/``:*
The given path is an absolute node path and is treated as such.
Example: ``/sites/acmecom/home/about/us``

*``node`` does not start with ``/``:*
The given path is treated as a path relative to the current node.
Examples: given that the current node is ``/sites/acmecom/products/``,
``stapler`` results in ``/sites/acmecom/products/stapler``,
``../about`` results in ``/sites/acmecom/about/``,
``./neos/info`` results in ``/sites/acmecom/products/neos/info``.

*``node`` starts with a tilde character (``~``):*
The given path is treated as a path relative to the current site node.
Example: given that the current node is ``/sites/acmecom/products/``,
``~/about/us`` results in ``/sites/acmecom/about/us``,
``~`` results in ``/sites/acmecom``.

:Implementation: TYPO3\\Neos\\ViewHelpers\\Uri\\NodeViewHelper




Arguments
*********

* ``node`` (mixed, *optional*): A node object or a string node path (absolute or relative) or NULL to resolve the current document node

* ``format`` (string, *optional*): Format to use for the URL, for example "html" or "json

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``arguments`` (array, *optional*): Additional arguments to be passed to the UriBuilder (for example pagination parameters)

* ``section`` (string, *optional*)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

* ``baseNodeName`` (string, *optional*): The name of the base node inside the TypoScript context to use for the ContentContext or resolving relative paths

* ``resolveShortcuts`` (boolean, *optional*): INTERNAL Parameter - if FALSE, shortcuts are not redirected to their target. Only needed on rare backend occasions when we want to link to the shortcut itself.




Examples
********

**Default**::

	<neos:uri.node />


Expected result::

	homepage/about.html
	(depending on current workspace, current node, format etc.)


**Generating an absolute URI**::

	<neos:uri.node absolute="{true"} />


Expected result::

	http://www.example.org/homepage/about.html
	(depending on current workspace, current node, format, host etc.)


**Target node given as absolute node path**::

	<neos:uri.node node="/sites/acmecom/about/us" />


Expected result::

	about/us.html
	(depending on current workspace, current node, format etc.)


**Target node given as relative node path**::

	<neos:uri.node node="~/about/us" />


Expected result::

	about/us.html
	(depending on current workspace, current node, format etc.)



