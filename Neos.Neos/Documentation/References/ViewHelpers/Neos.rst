.. _`Neos ViewHelper Reference`:

Neos ViewHelper Reference
=========================

This reference was automatically generated from code on 2022-08-30


.. _`Neos ViewHelper Reference: neos:backend.authenticationProviderLabel`:

neos:backend.authenticationProviderLabel
----------------------------------------

Renders a label for the given authentication provider identifier

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\AuthenticationProviderLabelViewHelper




Arguments
*********

* ``identifier`` (string): The identifier to render the label for




.. _`Neos ViewHelper Reference: neos:backend.changeStats`:

neos:backend.changeStats
------------------------

Displays a text-based "bar graph" giving an indication of the amount and type of
changes done to something. Created for use in workspace management.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\ChangeStatsViewHelper




Arguments
*********

* ``changeCounts`` (array): Expected keys: new, changed, removed




.. _`Neos ViewHelper Reference: neos:backend.colorOfString`:

neos:backend.colorOfString
--------------------------

Generates a color code for a given string

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\ColorOfStringViewHelper




Arguments
*********

* ``string`` (string, *optional*): This is hashed (MD%) and then used as base for the resulting color, if not given the children are used

* ``minimalBrightness`` (integer, *optional*): Brightness, from 0 to 255




.. _`Neos ViewHelper Reference: neos:backend.configurationCacheVersion`:

neos:backend.configurationCacheVersion
--------------------------------------

ViewHelper for rendering the current version identifier for the
configuration cache.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\ConfigurationCacheVersionViewHelper





.. _`Neos ViewHelper Reference: neos:backend.configurationTree`:

neos:backend.configurationTree
------------------------------

Render HTML markup for the full configuration tree in the Neos Administration -> Configuration Module.

For performance reasons, this is done inside a ViewHelper instead of Fluid itself.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\ConfigurationTreeViewHelper




Arguments
*********

* ``configuration`` (array): Configuration to show




.. _`Neos ViewHelper Reference: neos:backend.cssBuiltVersion`:

neos:backend.cssBuiltVersion
----------------------------

Returns a shortened md5 of the built CSS file

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\CssBuiltVersionViewHelper





.. _`Neos ViewHelper Reference: neos:backend.documentBreadcrumbPath`:

neos:backend.documentBreadcrumbPath
-----------------------------------

Render a bread crumb path by using the labels of documents leading to the given node path

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\DocumentBreadcrumbPathViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node): Node




.. _`Neos ViewHelper Reference: neos:backend.ifModuleAccessible`:

neos:backend.ifModuleAccessible
-------------------------------

Condition ViewHelper that can evaluate whether the currently authenticated user can access a given Backend module

Note: This is a quick fix for https://github.com/neos/neos-development-collection/issues/2854
that will be obsolete once the whole Backend module logic is rewritten

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\IfModuleAccessibleViewHelper




Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``condition`` (boolean, *optional*): Condition expression conforming to Fluid boolean rules

* ``modulePath`` (string): Path of the module to evaluate

* ``moduleConfiguration`` (array): Configuration of the module to evaluate




.. _`Neos ViewHelper Reference: neos:backend.interfaceLanguage`:

neos:backend.interfaceLanguage
------------------------------

ViewHelper for rendering the current backend users interface language.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\InterfaceLanguageViewHelper





.. _`Neos ViewHelper Reference: neos:backend.isAllowedToEditUser`:

neos:backend.isAllowedToEditUser
--------------------------------

Returns true, if the current user is allowed to edit the given user, false otherwise.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\IsAllowedToEditUserViewHelper




Arguments
*********

* ``user`` (Neos\Neos\Domain\Model\User): The user subject




.. _`Neos ViewHelper Reference: neos:backend.javascriptConfiguration`:

neos:backend.javascriptConfiguration
------------------------------------

ViewHelper for the backend JavaScript configuration. Renders the required JS snippet to configure
the Neos backend.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\JavascriptConfigurationViewHelper





.. _`Neos ViewHelper Reference: neos:backend.translate`:

neos:backend.translate
----------------------

Returns translated message using source message or key ID.
uses the selected backend language
* Also replaces all placeholders with formatted versions of provided values.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\TranslateViewHelper




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

	<neos:backend.translate arguments="{0: 'foo', 1: '99.9'}">
	     <![CDATA[Untranslated {0} and {1,number}]]>
	</neos:backend.translate>


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

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\UserInitialsViewHelper




Arguments
*********

* ``format`` (string, *optional*): Supported are "fullFirstName", "initials" and "fullName"




.. _`Neos ViewHelper Reference: neos:backend.xliffCacheVersion`:

neos:backend.xliffCacheVersion
------------------------------

ViewHelper for rendering the current version identifier for the
xliff cache.

:Implementation: Neos\\Neos\\ViewHelpers\\Backend\\XliffCacheVersionViewHelper





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

:Implementation: Neos\\Neos\\ViewHelpers\\ContentElement\\EditableViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``property`` (string): Name of the property to render. Note: If this tag has child nodes, they overrule this argument!

* ``tag`` (string, *optional*): The name of the tag that should be wrapped around the property. By default this is a <div>

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): The node of the content element. Optional, will be resolved from the Fusion context by default




.. _`Neos ViewHelper Reference: neos:contentElement.wrap`:

neos:contentElement.wrap
------------------------

A view helper for manually wrapping content editables.

Note that using this view helper is usually not necessary as Neos will automatically wrap editables of content
elements.

By explicitly wrapping template parts with node meta data that is required for the backend to show properties in the
inspector, this ViewHelper enables usage of the ``contentElement.editable`` ViewHelper outside of content element
templates. This is useful if you want to make properties of a custom document node inline-editable.

:Implementation: Neos\\Neos\\ViewHelpers\\ContentElement\\WrapViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): Node




.. _`Neos ViewHelper Reference: neos:getType`:

neos:getType
------------

View helper to check if a given value is an array.

:Implementation: Neos\\Neos\\ViewHelpers\\GetTypeViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The value to get the type of




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




.. _`Neos ViewHelper Reference: neos:link.module`:

neos:link.module
----------------

A view helper for creating links to modules.

:Implementation: Neos\\Neos\\ViewHelpers\\Link\\ModuleViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

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

* ``path`` (string): Target module path

* ``action`` (string, *optional*): Target module action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html"

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = true




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
at all, the generated URI will refer to the current document node inside the Fusion context.

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

:Implementation: Neos\\Neos\\ViewHelpers\\Link\\NodeViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

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

* ``node`` (mixed, *optional*): A node object, a string node path (absolute or relative), a string node://-uri or NULL

* ``format`` (string, *optional*): Format to use for the URL, for example "html" or "json"

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``arguments`` (array, *optional*): Additional arguments to be passed to the UriBuilder (for example pagination parameters)

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = true

* ``baseNodeName`` (string, *optional*): The name of the base node inside the Fusion context to use for the ContentContext or resolving relative paths

* ``nodeVariableName`` (string, *optional*): The variable the node will be assigned to for the rendered child content




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


**Target node given as node://-uri**::

	<neos:link.node node="node://30e893c1-caef-0ca5-b53d-e5699bb8e506">Corporate imprint</neos:link.node>


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


**Dynamic tag content involving the linked node&#039;s properties**::

	<neos:link.node node="about-us">see our <span>{linkedNode.label}</span> page</neos:link.node>


Expected result::

	<a href="about-us.html">see our <span>About Us</span> page</a>
	(depending on current workspace, current node, format etc.)




.. _`Neos ViewHelper Reference: neos:node.closestDocument`:

neos:node.closestDocument
-------------------------

ViewHelper to find the closest document node to a given node

:Implementation: Neos\\Neos\\ViewHelpers\\Node\\ClosestDocumentViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node): Node




.. _`Neos ViewHelper Reference: neos:rendering.inBackend`:

neos:rendering.inBackend
------------------------

ViewHelper to find out if Neos is rendering the backend.

:Implementation: Neos\\Neos\\ViewHelpers\\Rendering\\InBackendViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): Node




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

:Implementation: Neos\\Neos\\ViewHelpers\\Rendering\\InEditModeViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): Optional Node to use context from

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

:Implementation: Neos\\Neos\\ViewHelpers\\Rendering\\InPreviewModeViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): Optional Node to use context from

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
Make sure you either give a node from the current context to
the ViewHelper or have "node" set as template variable at least.

:Implementation: Neos\\Neos\\ViewHelpers\\Rendering\\LiveViewHelper




Arguments
*********

* ``node`` (Neos\ContentRepository\Core\Projection\ContentGraph\Node, *optional*): Node




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

:Implementation: Neos\\Neos\\ViewHelpers\\StandaloneViewViewHelper




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

:Implementation: Neos\\Neos\\ViewHelpers\\Uri\\ModuleViewHelper




Arguments
*********

* ``path`` (string): Target module path

* ``action`` (string, *optional*): Target module action

* ``arguments`` (string, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html"

* ``additionalParams`` (string, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (string, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (string, *optional*): arguments to be removed from the URI. Only active if $addQueryString = true




Examples
********

**Defaults**::

	<link rel="some-module" href="{neos:uri.module(path: 'system/useradmin')}" />


Expected result::

	<link rel="some-module" href="neos/system/useradmin" />




.. _`Neos ViewHelper Reference: neos:uri.node`:

neos:uri.node
-------------

A view helper for creating URIs pointing to nodes.

The target node can be provided as string or as a Node object; if not specified
at all, the generated URI will refer to the current document node inside the Fusion context.

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

:Implementation: Neos\\Neos\\ViewHelpers\\Uri\\NodeViewHelper




Arguments
*********

* ``node`` (mixed, *optional*): A node object, a string node path (absolute or relative), a string node://-uri or NULL

* ``format`` (string, *optional*): Format to use for the URL, for example "html" or "json"

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``arguments`` (array, *optional*): Additional arguments to be passed to the UriBuilder (for example pagination parameters)

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = true

* ``baseNodeName`` (string, *optional*): The name of the base node inside the Fusion context to use for the ContentContext or resolving relative paths

* ``nodeVariableName`` (string, *optional*): The variable the node will be assigned to for the rendered child content

* ``resolveShortcuts`` (boolean, *optional*): INTERNAL Parameter - if false, shortcuts are not redirected to their target. Only needed on rare backend occasions when we want to link to the shortcut itself




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


**Target node given as node://-uri**::

	<neos:uri.node node="node://30e893c1-caef-0ca5-b53d-e5699bb8e506" />


Expected result::

	about/us.html
	(depending on current workspace, current node, format etc.)



