.. _`TYPO3 Fluid ViewHelper Reference`:

TYPO3 Fluid ViewHelper Reference
================================

This reference was automatically generated from code on 2017-03-29


.. _`TYPO3 Fluid ViewHelper Reference: f:format.raw`:

f:format.raw
------------

Outputs an argument/value without any escaping. Is normally used to output
an ObjectAccessor which should not be escaped, but output as-is.

PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
as the output is NOT SANITIZED!

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\RawViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The value to output




Examples
********

**Child nodes**::

	<f:format.raw>{string}</f:format.raw>


Expected result::

	(Content of {string} without any conversion/escaping)


**Value attribute**::

	<f:format.raw value="{string}" />


Expected result::

	(Content of {string} without any conversion/escaping)


**Inline notation**::

	{string -> f:format.raw()}


Expected result::

	(Content of {string} without any conversion/escaping)



