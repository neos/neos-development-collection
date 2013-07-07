.. _TypoScript Processor Reference:

TypoScript Processor Reference
==============================

This reference was automatically generated from code on 2013-05-07


crop
----

Crops a part of a string and optionally replaces the cropped part by a string.

Implementated in: TYPO3\\TypoScript\\Processors\\CropProcessor




Arguments
*********

* ``maximumCharacters`` (integer):   The maximum number of characters to which the subject shall be shortened.

* ``preOrSuffixString`` (string):   The string which is to be prepended or appended to the cropped
  subject if the subject has been cropped at all.

* ``options`` (integer):   A bitmask combination of the CROP_* constants:

  * CROP_FROM_BEGINNING: If set, the beginning of the string will be cropped instead of the end.
  * CROP_AT_WORD: The string will be of the maximum length specified by $maximumCharacters, but it will be cropped after a word instead of probably the middle of a word.
  * CROP_AT_SENTENCE: The string will be of the maximum length specified by $maximumCharacters, but it will be cropped after a sentence instead of probably the middle of a word.




date
----

Transforms an UNIX timestamp according to the given format.
For the possible format values, look at the php date() function.

Please note that the incoming UNIX timestamp is intrinsically considered UTC,
if another time zone is intended, the setTimezone() setter has to be used.
Using this, the original time will be shifted accordingly, meaning the timestamp
1185279917 representing UTC 2007-07-24 12:25:17 will result into Japan 2007-07-24 21:25:17
in case the setTimezone() is set to 'Japan' timezone.

Implementated in: TYPO3\\TypoScript\\Processors\\DateProcessor




Arguments
*********

* ``format`` (string):   Set the format to use, according to the rules of the php date() function.

* ``timezone`` (string):   Sets the timezone to apply, see http://php.net/manual/en/timezones.php.




if
--

Returns the trueValue when the condition evaluates to TRUE, otherwise
the falseValue is returned.

If the condition is an object, it is cast to a string for evaluation.

The following conditions are considered TRUE:

- boolean TRUE
- number > 0
- non-empty string

While these conditions evaluate to FALSE:

- boolean FALSE
- number <= 0
- empty string

Implementated in: TYPO3\\TypoScript\\Processors\\IfProcessor




Arguments
*********

* ``condition`` (boolean):   The condition for the if clause, or simply TRUE/FALSE.

* ``trueValue`` (string):   The value to return if the condition is TRUE.

* ``falseValue`` (string):   The value to return if the condition is FALSE.




ifBlank
-------

Overrides the subject with the given value, if the subject (not trimmed) is empty.

Implementated in: TYPO3\\TypoScript\\Processors\\IfBlankProcessor




Arguments
*********

* ``replacement`` (string):   The value that overrides the subject.




ifEmpty
-------

Overrides the subject with the given value, if the subject (trimmed) is empty.

Implementated in: TYPO3\\TypoScript\\Processors\\IfEmptyProcessor




Arguments
*********

* ``replacement`` (string):   The replacement to override the subject.




multiply
--------

Multiplies a number or numeric string with the given factor.

Implementated in: TYPO3\\TypoScript\\Processors\\MultiplyProcessor




Arguments
*********

* ``factor`` (integer):   The factor to multiply the subject with.




override
--------

Overrides the subject with the given value, if the value is not empty.

Implementated in: TYPO3\\TypoScript\\Processors\\OverrideProcessor




Arguments
*********

* ``replacement`` (string):   The value that overrides the subject.




replace
-------

Replaces a part of the subject with something else.

Implementated in: TYPO3\\TypoScript\\Processors\\ReplaceProcessor




Arguments
*********

* ``search`` (string):   The string to search for.

* ``replace`` (string):   The string to replace matches with.




round
-----

Rounds the subject if it is a float value. If an integer is given, nothing happens.

Implementated in: TYPO3\\TypoScript\\Processors\\RoundProcessor




Arguments
*********

* ``precision`` (integer):   The number of digits after the decimal point. Negative values are also supported (-1 rounds to full 10ths).




shiftCase
---------

Shifts the case of a string into the specified direction.

Implementated in: TYPO3\\TypoScript\\Processors\\ShiftCaseProcessor




Arguments
*********

* ``direction`` (string):   The direction to shift case in, one of

  * SHIFT_CASE_TO_UPPER (upper)
  * SHIFT_CASE_TO_LOWER (lower)
  * SHIFT_CASE_TO_TITLE (title)




substring
---------

Returns a substring of the subject.

Implementated in: TYPO3\\TypoScript\\Processors\\SubstringProcessor




Arguments
*********

* ``start`` (integer):   The left boundary of the substring.

* ``length`` (integer):   The length of the substring.




toInteger
---------

Converts the subject to an integer.

Implementated in: TYPO3\\TypoScript\\Processors\\ToIntegerProcessor





trim
----

Trims the subject (removes whitespace around the value).

Implementated in: TYPO3\\TypoScript\\Processors\\TrimProcessor





wrap
----

Wraps the specified string into a prefix and a suffix string.

Implementated in: TYPO3\\TypoScript\\Processors\\WrapProcessor




Arguments
*********

* ``prefix`` (string):   The string to prepend.

* ``suffix`` (string):   The string to append.



