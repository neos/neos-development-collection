.. _`TypoScript ViewHelper Reference`:

TypoScript ViewHelper Reference
===============================

This reference was automatically generated from code on 2016-06-07


.. _`TypoScript ViewHelper Reference: ts:render`:

ts:render
---------

Render a TypoScript object with a relative TypoScript path, optionally
pushing new variables onto the TypoScript context.

:Implementation: TYPO3\\TypoScript\\ViewHelpers\\RenderViewHelper




Arguments
*********

* ``path`` (string): Relative TypoScript path to be rendered

* ``context`` (array, *optional*): Additional context variables to be set.

* ``typoScriptPackageKey`` (string, *optional*): The key of the package to load TypoScript from, if not from the current context.

* ``typoScriptFilePathPattern`` (string, *optional*): Resource pattern to load TypoScript from. Defaults to: resource://@package/Private/TypoScript/




Examples
********

**Simple**::

	TypoScript:
	some.given {
		path = TYPO3.TypoScript:Template
		…
	}
	ViewHelper:
	<ts:render path="some.given.path" />


Expected result::

	(the evaluated TypoScript, depending on the given path)


**TypoScript from a foreign package**::

	<ts:render path="some.given.path" typoScriptPackageKey="Acme.Bookstore" />


Expected result::

	(the evaluated TypoScript, depending on the given path)



