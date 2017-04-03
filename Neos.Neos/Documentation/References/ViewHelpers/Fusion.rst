.. _`Fusion ViewHelper Reference`:

Fusion ViewHelper Reference
===========================

This reference was automatically generated from code on 2017-03-30


.. _`Fusion ViewHelper Reference: fusion:render`:

fusion:render
-------------

Render a Fusion object with a relative Fusion path, optionally
pushing new variables onto the Fusion context.

:Implementation: Neos\\Fusion\\ViewHelpers\\RenderViewHelper




Arguments
*********

* ``path`` (string): Relative Fusion path to be rendered

* ``context`` (array, *optional*): Additional context variables to be set.

* ``typoScriptPackageKey`` (string, *optional*): The key of the package to load Fusion from, if not from the current context.

* ``typoScriptFilePathPattern`` (string, *optional*): Resource pattern to load Fusion from. Defaults to: resource://@package/Private/Fusion/




Examples
********

**Simple**::

	Fusion:
	some.given {
		path = Neos.Fusion:Template
		…
	}
	ViewHelper:
	<ts:render path="some.given.path" />


Expected result::

	(the evaluated Fusion, depending on the given path)


**Fusion from a foreign package**::

	<ts:render path="some.given.path" typoScriptPackageKey="Acme.Bookstore" />


Expected result::

	(the evaluated Fusion, depending on the given path)



