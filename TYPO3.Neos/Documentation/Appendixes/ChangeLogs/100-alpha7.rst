.. _1.0.0-alpha7:

=========================
1.0.0-alpha7 (2013-10-30)
=========================

TYPO3 Neos Alpha 7 contains a number of improvements and fixes we considered important enough
to release another alpha.

This the **the last last alpha release** of TYPO3 Neos 1.0, the next release will be a beta.

Main highlights are:

* **Support for internal links**. Neos now has support for creating internal links, including
  auto-completion when adding a link.
* **Wireframe Mode improvements**. It is now better readable and less prone to conflicts with
  special elements.
* **Demo site improvements**, the site is in a much better state than fpr alpha 6.
* **Stability improvements**, like the ability to install Neos in a subfolder, duplicated
  ghost icons, styling fixes, …
* **Cleanup** of code, UI, default TypoScript files and documentation.


Full list of breaking changes
=============================

Two changes are considered breaking.

*TYPO3.Neos*

* Cleanup: requirejs is moved to the library folder (https://review.typo3.org/24621)

  Only breaking if you've overriden the NeosBackendHeaderData template (before alpha6
  this template was named PageHead).

* Settings.yaml is restructured to be more extensible (https://review.typo3.org/24615)

  The change is breaking as the TYPO3.Neos.loadMinifiedJavascript setting has changed.
  This change will have to be adopted by all developers working on the Neos user interface.

* Graceful degradation of content elements (https://review.typo3.org/24239)

  Considered breaking because a setting (``handleRenderingException``) has been replaced
  with a new, more flexible setting::

    TYPO3:
      TypoScript:
        rendering
          exceptionHandler: 'TYPO3\\TypoScript\\Core\\ExceptionHandlers\\XmlCommentHandler'


Detailed change log
===================

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Base Distribution
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Remove composer.lock for master
-----------------------------------------------------------------------------------------

* Commit: `df4a2ed <http://git.typo3.org/Neos/Distributions/Base.git?a=commit;h=df4a2ed4258b7eb76df727997f1efa314c25d9f6>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Graceful degradation of broken content elements
-----------------------------------------------------------------------------------------

If content elements cannot be rendered for some reason, an error message
is displayed instead of the actual content. The broken elements can
still be (un)hidden, copy-pasted, … and even published.

Some errors are not yet covered yet, like not existing Node-Types.
In this case the error message is displayed without the option to hide,
remove, cut or copy the broken element.
There is already a request for this Feature #52511.

The css-styles of the Neos backend do not apply to published, broken
content elements, but can be customized.

The TypoScript object type TYPO3.Neos:Template declares the
@exceptionHandler property to handle rendering exceptions with a special
handler that wraps nodes with the content element wrapping services.

* Resolves: `#45531 <http://forge.typo3.org/issues/45531>`_
* Commit: `b5b2841 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=b5b28414274cfb00c7b378847f8ea1e60c6f93f5>`_

[TASK] Update PluginView documentation in Integrator Guide
-----------------------------------------------------------------------------------------

During the review of the flexible plugin integration we change
the YAML configuration. This patch update the integrator guide
to use the correct syntax.

* Commit: `b12dee6 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=b12dee69ae341ed8359dc164c42bd7990c94ed6c>`_

[TASK] Find closest document node in TypoScriptView
-----------------------------------------------------------------------------------------

* Commit: `2479275 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=2479275f6f31f15e5b481b0cb412260ef276dbb8>`_

[BUGFIX] Fix font-family for wireframe mode
-----------------------------------------------------------------------------------------

* Commit: `5dbfed9 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=5dbfed999bf16b9d1abcc6c3fcda730b60f6706f>`_

[BUGFIX] Neos does not work correctly if not installed in docroot
-----------------------------------------------------------------------------------------

If Neos is hosted in a subfolder of the document root the integrator
has to set the the RewriteBase to /subfolder/Web/. If this is done the
rewrite to /@user-<username>.html redirects to the wrong page.

Besides that the menu points to the wrong location for the content
module, and the full backend interface is not loaded if the site
is put in a subfolder 'neos' because then the isBackendModule()
check fails.

* Resolves: `#52962 <http://forge.typo3.org/issues/52962>`_
* Commit: `5854d39 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=5854d39a8aaa227eb1fe620586eddb14e051c49d>`_

[BUGFIX] Duplicated ghost icons are shown when including Bootstrap
-----------------------------------------------------------------------------------------

To prevent duplicated ghost icons in the neos user interface when
using Bootstrap or font-awesome, we need to make sure they're not shown.

* Commit: `0cf65de <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=0cf65de7236030e144a31a241d474d4201626b3c>`_

[TASK] Support internal links to any document type
-----------------------------------------------------------------------------------------

Currently the internal links support only TYPO3.Neos:Page. This
patch change the default behavior to TYPO3.Neos:Document to
allow creating link to any node types based on the abstract
TYPO3.Neos:Document type.

* Commit: `3e9f8af <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=3e9f8aff557e1e08a8997d7a83d4447d1e7f82d8>`_

[FEATURE] Wireframe mode switched to typeplate, improved readability
-----------------------------------------------------------------------------------------

* Commit: `034e620 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=034e620cdc84580bd8dad30ec43042a28a9cb2ff>`_

[TASK] Remove unused antiscroll library
-----------------------------------------------------------------------------------------

* Commit: `b6c9aa3 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=b6c9aa3e477c98bd29ee64a27a8252fd2af974a8>`_

[BUGFIX] Remove unused use in NodeController
-----------------------------------------------------------------------------------------

In the NodeController class the TYPO3\\Flow\\Utility\\Arrays are
included but not used. This commit removes that use declaration.

* Commit: `e64048e <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=e64048e4431cd2b3d2138210426a86cc7ee4173c>`_

[BUGFIX] Fix hover issues on package module buttons
-----------------------------------------------------------------------------------------

* Commit: `68eeed6 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=68eeed6e8a2b694e33a22d7261b1c4c868975c12>`_

[BUGFIX] Fixes rendering of Shortcut in the Backend
-----------------------------------------------------------------------------------------

* Commit: `f7be9b9 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=f7be9b9fa8e9682857658f6e6167e8a0653ccf6a>`_

[TASK] Make inspector discard button wider
-----------------------------------------------------------------------------------------

This is done to show the whole label of the button.

* Commit: `fcf88c4 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=fcf88c4ca9f5fbaa4e02a2560eb4085d57de1176>`_

[TASK] Update labels in TYPO3.Neos
-----------------------------------------------------------------------------------------

This is the labels change update from Mathias Schreiber and
Jacob Floyd.

* Commit: `5b67596 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=5b67596cadd8b096069d662174fc4af55dbfc0ae>`_

[TASK] Rename remaining neos-btn-* classes to neos-button-*
-----------------------------------------------------------------------------------------

This commit removes all refrences to neos-btn* class and replaces
it with neos-button* instead.
* Related: `#49856 <http://forge.typo3.org/issues/49856>`_

* Commit: `9ad5301 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=9ad5301b64da79daa25d48535aace00f637623f2>`_

[FEATURE] Closest FlowQuery operation
-----------------------------------------------------------------------------------------

Introduce a closest FlowQuery operation capable of finding the nearest
node, including itself, of a certain node type for each node in the
FlowQuery context.

* Resolves: `#53017 <http://forge.typo3.org/issues/53017>`_
* Commit: `3aa40fe <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=3aa40feda4398b6385fdbe803e3222f616613760>`_

[BUGFIX] PluginView broken due to missing TypoScript
-----------------------------------------------------------------------------------------

The prototype for PluginView is not include in DefaultTypoScript.ts2

* Commit: `762b5d4 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=762b5d4eb5160d76431707f348222f9b3f40692b>`_

[BUGFIX] fix choosing format styles
-----------------------------------------------------------------------------------------

* added h1, … to the list of allowed tags
* the chosen list for triggering a change has been modified from "liszt:change"
  to "chosen:updated.chosen". This makes sure the selector updates when choosing
  a different style.

* Commit: `cca6533 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=cca6533f702fe7f2bd024c2707813f604ff322ae>`_

[BUGFIX] The 404 error page is rendered outside viewport
-----------------------------------------------------------------------------------------

The 404 page is styled incorrectly and because of this not
displayed inside the screen, but only partly visible in the top
right corner of the screen.

This change cleans up the dependencies of the error screen on
bootstrap and minifies the CSS used. Besides that it adds
responsive styling for the error page to display it in the
center of the page again.

* Resolves: `#52711 <http://forge.typo3.org/issues/52711>`_
* Commit: `01d95d5 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=01d95d504c536d01c32fc5e24bf9fb4389c8aac5>`_

[TASK] Streamline button styling
-----------------------------------------------------------------------------------------

This commit removes all refrences to neos-btn class and replaces
it with neos-button instead.

* Resolves: `#49856 <http://forge.typo3.org/issues/49856>`_
* Commit: `2b52e8f <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=2b52e8fa715dfea65c525f0618c772d6c11a8107>`_

[FEATURE] Support editing internal links with Aloha
-----------------------------------------------------------------------------------------

Adds an aloha repository plugin that interacts with the node REST API
introduced with I0af30c40cf1d5bcdedfd39d44f51cfc6ee01565b providing
a typeahead-functionality for page-links.

The resulting URI will have the following format: node://<UUID>
Those can be replaced with proper URIs using the ConvertNodeUris
TypoScript Object.

This is done for text and headlines with
Id90db00ff9e3e23e3995e954c0c0b17bf5c3c446

* Resolves: `#48366 <http://forge.typo3.org/issues/48366>`_
* Commit: `a4ee4d9 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=a4ee4d99efbd2e857546924f4a8a70bac1ff73a8>`_

[FEATURE] Node REST API
-----------------------------------------------------------------------------------------

Base of a (currently readonly) REST API for the TYPO3CR

Split apart from Id5194cc45fb4a2efa812f0757886f162898c6cf9

* Commit: `6e2666a <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=6e2666a17d01ad3ad72e787bbce430716cf7dcb7>`_

[TASK] render the Integrators Cookbook
-----------------------------------------------------------------------------------------

* Commit: `70a36fc <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=70a36fc58f24cfbb8366593914e77532c2c97fd0>`_

[TASK] Add "Select Page Layout" documentation
-----------------------------------------------------------------------------------------

This commit adds a "Select Page Layout" documentation
part to the Integrator Cookbook.

* Commit: `c635482 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=c63548208cbb9602ddda8896ddf38223c31ff2e5>`_

[TASK] Update positions of the default TypoScript
-----------------------------------------------------------------------------------------

Updates @position properties of some array items to be more specific.

* Commit: `08b9f37 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=08b9f37da4b71a1d699c8b1f63303591c6ff566a>`_

[TASK] Documentation update
-----------------------------------------------------------------------------------------

The processor documentation was confusing as the property name
used was value, which is the same string as used for the value
to be wrapped.

Credits to Henjo Hoeksma for finding

* Commit: `285043d <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=285043da4089ec01a8937b28416868af9a3102a2>`_

[!!!][TASK] Restructure TYPO3.Neos Settings.yaml structure
-----------------------------------------------------------------------------------------

This restructures the Settings.yaml to be more extensible. The change
is marked breaking as the TYPO3.Neos.loadMinifiedJavascript setting
has changed. This change will have to be adopted by all developers working
on the Neos user interface.

* Commit: `673a97c <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=673a97c519d59f4062ff3cd77bb4e194aef82651>`_

[BUGFIX] Setup wizard has incorrect styling
-----------------------------------------------------------------------------------------

The setup wizard does not include the prefixed Neos css but plain
bootstrap. For this reason we should not prefix the classes used.

* Related: `#52175 <http://forge.typo3.org/issues/52175>`_
* Commit: `59ce076 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=59ce076de619dfca39faf8356fea7fbc38a1ed82>`_

[!!!][TASK] Move requirejs and plugin to Library folder
-----------------------------------------------------------------------------------------

The requirejs library was still in the wrong folder. This change
moves the file to the Library folder but is breaking for people
who have overridden the Neos templates like PageHead.html

* Commit: `73501ab <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=73501ab303385451544787bd2a331fd9a43794ab>`_

[BUGFIX] Tab index in inspector is broken
-----------------------------------------------------------------------------------------

When the userinterface has focus in the inspector for example, and the
user moves to another field by pressing tab the content element selection
in the website body is changed and the focus moves to the first
inlineeditable and is as such moving the focus to a for the user
unexpected location.

This change keeps track if the focus is within the #neos-application
or not, and if so the node selection change is ignored.

* Commit: `cc87aa9 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=cc87aa97babf06e2f91c64ca40a2edbbdc9012b4>`_

[TASK] Avoid empty ActionName in PluginImplementation
-----------------------------------------------------------------------------------------

Else, the system later fails with the message:
"The action name must not be an empty string."

* Resolves: `#52964 <http://forge.typo3.org/issues/52964>`_
* Commit: `3d6a2d2 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=3d6a2d280d40e949750e74e4ba953d267b14dcca>`_

[TASK] Refactor TypoScript syntax
-----------------------------------------------------------------------------------------

Use shorter syntax where possible.

* Commit: `a76f821 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=a76f82112b47a07be74b7b05eba1867cff77fcf5>`_

[BUGFIX] Make nodePath property configurable on PrimaryContent object
-----------------------------------------------------------------------------------------

The PrimaryContent prototype is not easily usable after the refactoring
from PrimaryContentCollection.

This change allows to configure the nodePath on the PrimaryContent
object which will be used on the default ContentCollection to render
the correct nodes.

The rendering functional test is updated to include testing of the
PrimaryContent object.

* Fixes: `#52911 <http://forge.typo3.org/issues/52911>`_
* Commit: `9d42844 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=9d4284433dd7d17b7a42cc02a88717c8f229a162>`_

[TASK] Refactor default TypoScript to separate files
-----------------------------------------------------------------------------------------

Split default TypoScript to separate files and update the functional
test fixture to use the default TypoScript instead of re-declaring
everything.

* Commit: `c5ad3ca <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=c5ad3cacacbfac719bc8a07fa82c2c909e912220>`_

[TASK] Yaml cleanup
-----------------------------------------------------------------------------------------

This change removes the yaml configuration that was cleaned up in
I61212ebc08b4824f6e8be7a1b6a60207fc98e40b. Moved to a separate change
as it's not related to that change.

* Commit: `0b602f6 <http://git.typo3.org/Packages/TYPO3.Neos.git?a=commit;h=0b602f61faf423595be05ff9e44e3f1300a0263d>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos.NodeTypes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[FEATURE] Convert "node://" URIs in Text and Headline TS objects
-----------------------------------------------------------------------------------------

converts node URIs to proper URIs by applying the ConvertNodeUris
processor to Text & Headline text properties.

* Depends: Ib7c8c6cc7bc53d0f1f7e21b5930cba2c97ea3475
* Related: `#48366 <http://forge.typo3.org/issues/48366>`_
* Commit: `91cdf09 <http://git.typo3.org/Packages/TYPO3.Neos.NodeTypes.git?a=commit;h=91cdf0911691e37e61074f8cdbadcf2ef1f5abd1>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.SiteKickstarter
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TYPO3CR
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[!!!][BUGFIX] NodeConverter should work with the node identifier
-----------------------------------------------------------------------------------------

This adjusts the NodeConverter so that it expects a NodeData
identifier instead of the "Persistence_Object_Identifier" when
dealing with UUIDs.

This is a breaking change if the NodeConverter is expected to convert
a UUID returned by the PersistenceManager to a Node instance.

* Commit: `01a1d9c <http://git.typo3.org/Packages/TYPO3.TYPO3CR.git?a=commit;h=01a1d9c674669a5e5b4a3ff2d8c64447a84a2902>`_

[FEATURE] NodeConverter can convert UUIDs
-----------------------------------------------------------------------------------------

This extends the NodeConverter so that it can convert node identifier
in addition to node context paths.

If a valid UUID is passed to the NodeConverter it is expected to be
the *technical identifier* of a Node. Because the context is lost in
this case a UUID always returns a Node of the *live* workspace!
* Commit: `bee5b3d <http://git.typo3.org/Packages/TYPO3.TYPO3CR.git?a=commit;h=bee5b3db711c4cdfcc243d8898e6cafb9f571b7d>`_

[TASK] Introduce signals for node publising
-----------------------------------------------------------------------------------------

This adds two signals ``beforeNodePublishing`` and
``afterNodePublishing`` to the Workspace class.
Those signals are triggered whenever a node is being published to a
different workspace (usually the "live" workspace).

Besides this adjusts Workspace::publish() so that it actually works
with NodeInterface rather than with NodeData instances.

* Commit: `ef44b1c <http://git.typo3.org/Packages/TYPO3.TYPO3CR.git?a=commit;h=ef44b1cddafd536c027a98aa21ddc161d66796e8>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TypoScript
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[!!!][BUGFIX] Graceful degradation of broken content elements
-----------------------------------------------------------------------------------------

If content elements cannot be rendered for some reason, an error message
is displayed instead of the actual content. The broken elements can
still be (un)hidden, copy-pasted, … and even published.

The css-styles of the Neos backend do not apply to published, broken
content elements, but can be customized.

Configuration of TypoScript-Runtime changed to::

  TYPO3:
    TypoScript:
      rendering
        exceptionHandler: 'TYPO3\\TypoScript\\Core\\ExceptionHandlers\\XmlCommentHandler'

A valid configuration is any fully qualified type extending
TYPO3\\TypoScript\\Core\\ExceptionHandlers\\AbstractRenderingExceptionHandler.

This fix spreads over multiple packages:

* TYPO3.TypoScript
* TYPO3.Neos

* Resolves: `#45531 <http://forge.typo3.org/issues/45531>`_
* Commit: `572d238 <http://git.typo3.org/Packages/TYPO3.TypoScript.git?a=commit;h=572d2382731a6d374ca0b8ae9e7b1a96a6cfd1ff>`_

[BUGFIX] Support debug mode for CaseImplementation
-----------------------------------------------------------------------------------------

The Case object did not match result correctly in debug mode, since the
rendered output of a matcher is annotated with debug comments and does
not equal the MATCH_NORESULT constant.

This change strips these comments from the rendered output if the debug
mode is enabled before comparing the strings.

To test the behavior a setter for the debug mode was introduced to the
TypoScript Runtime and an option was added to the TypoScriptView.

* Fixes: `#52923 <http://forge.typo3.org/issues/52923>`_
* Commit: `405dd1c <http://git.typo3.org/Packages/TYPO3.TypoScript.git?a=commit;h=405dd1c3cb50c0dc46526cdc3f1b0b24388b0fd7>`_

[BUGFIX] Do not wrap arrays again in FlowQuery q() function
-----------------------------------------------------------------------------------------

The FlowQuery function q() in Eel should not always wrap the given
element in an array to assert q(q(value)) == q(value).

* Commit: `95da425 <http://git.typo3.org/Packages/TYPO3.TypoScript.git?a=commit;h=95da425771bec925fff47062a4d8321162761618>`_

[FEATURE] Allow additional properties for the Case TypoScript object
-----------------------------------------------------------------------------------------

All properties of a Case object were treated as matchers. This change
introduces a new meta property '@ignoreProperties' of type array to
configure properties that should be ignored when evaluating matchers.

This is only used for rare cases where properties need to be configured
on the Case object and passed down to matchers using context overrides.

* Related: `#52911 <http://forge.typo3.org/issues/52911>`_
* Commit: `f1cc3d8 <http://git.typo3.org/Packages/TYPO3.TypoScript.git?a=commit;h=f1cc3d8e1923c15e79e01cddf5553133e876fde7>`_

