.. _`Flow Validator Reference`:

Flow Validator Reference
========================

This reference was automatically generated from code on 2017-03-29


.. _`Flow Validator Reference: AggregateBoundaryValidator`:

AggregateBoundaryValidator
--------------------------

A validator which will not validate Aggregates that are lazy loaded and uninitialized.
Validation over Aggregate Boundaries can hence be forced by making the relation to
other Aggregate Roots eager loaded.

Note that this validator is not part of the public API and you should not use it manually.

Checks if the given value is valid according to the property validators.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: AlphanumericValidator`:

AlphanumericValidator
---------------------

Validator for alphanumeric strings.

The given $value is valid if it is an alphanumeric string, which is defined as [[:alnum:]].

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: BooleanValueValidator`:

BooleanValueValidator
---------------------

Validator for a specific boolean value.

Checks if the given value is a specific boolean value.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``expectedValue`` (boolean, *optional*): The expected boolean value




.. _`Flow Validator Reference: CollectionValidator`:

CollectionValidator
-------------------

A generic collection validator.

Checks for a collection and if needed validates the items in the collection.
This is done with the specified element validator or a validator based on
the given element type and validation group.

Either elementValidator or elementType must be given, otherwise validation
will be skipped.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``elementValidator`` (string, *optional*): The validator type to use for the collection elements

* ``elementValidatorOptions`` (array, *optional*): The validator options to use for the collection elements

* ``elementType`` (string, *optional*): The type of the elements in the collection

* ``validationGroups`` (string, *optional*): The validation groups to link to




.. _`Flow Validator Reference: CountValidator`:

CountValidator
--------------

Validator for countable things

The given value is valid if it is an array or \Countable that contains the specified amount of elements.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``minimum`` (integer, *optional*): The minimum count to accept

* ``maximum`` (integer, *optional*): The maximum count to accept




.. _`Flow Validator Reference: DateTimeRangeValidator`:

DateTimeRangeValidator
----------------------

Validator for checking Date and Time boundaries

Adds errors if the given DateTime does not match the set boundaries.

latestDate and earliestDate may be each <time>, <start>/<duration> or <duration>/<end>, where <duration> is an
ISO 8601 duration and <start> or <end> or <time> may be 'now' or a PHP supported format. (1)

In general, you are able to provide a timestamp or a timestamp with additional calculation. Calculations are done
as described in ISO 8601 (2), with an introducing "P". P7MT2H30M for example mean a period of 7 months, 2 hours
and 30 minutes (P introduces a period at all, while a following T introduces the time-section of a period. This
is not at least in order not to confuse months and minutes, both represented as M).
A period is separated from the timestamp with a forward slash "/". If the period follows the timestamp, that
period is added to the timestamp; if the period precedes the timestamp, it's subtracted.
The timestamp can be one of PHP's supported date formats (1), so also "now" is supported.

Use cases:

If you offer something that has to be manufactured and you ask for a delivery date, you might assure that this
date is at least two weeks in advance; this could be done with the expression "now/P2W".
If you have a library of ancient goods and want to track a production date that is at least 5 years ago, you can
express it with "P5Y/now".

Examples:

If you want to test if a given date is at least five minutes ahead, use
  earliestDate: now/PT5M
If you want to test if a given date was at least 10 days ago, use
  latestDate: P10D/now
If you want to test if a given date is between two fix boundaries, just combine the latestDate and earliestDate-options:
  earliestDate: 2007-03-01T13:00:00Z
  latestDate: 2007-03-30T13:00:00Z

Footnotes:

http://de.php.net/manual/en/datetime.formats.compound.php (1)
http://en.wikipedia.org/wiki/ISO_8601#Durations (2)
http://en.wikipedia.org/wiki/ISO_8601#Time_intervals (3)

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``latestDate`` (string, *optional*): The latest date to accept

* ``earliestDate`` (string, *optional*): The earliest date to accept




.. _`Flow Validator Reference: DateTimeValidator`:

DateTimeValidator
-----------------

Validator for DateTime objects.

Checks if the given value is a valid DateTime object.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``locale`` (string|Locale, *optional*): The locale to use for date parsing

* ``strictMode`` (boolean, *optional*): Use strict mode for date parsing

* ``formatLength`` (string, *optional*): The format length, see DatesReader::FORMAT_LENGTH_*

* ``formatType`` (string, *optional*): The format type, see DatesReader::FORMAT_TYPE_*




.. _`Flow Validator Reference: EmailAddressValidator`:

EmailAddressValidator
---------------------

Validator for email addresses

Checks if the given value is a valid email address.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: FloatValidator`:

FloatValidator
--------------

Validator for floats.

The given value is valid if it is of type float or a string matching the regular expression [0-9.e+-]

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: GenericObjectValidator`:

GenericObjectValidator
----------------------

A generic object validator which allows for specifying property validators.

Checks if the given value is valid according to the property validators.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: IntegerValidator`:

IntegerValidator
----------------

Validator for integers.

Checks if the given value is a valid integer.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: LabelValidator`:

LabelValidator
--------------

A validator for labels.

Labels usually allow all kinds of letters, numbers, punctuation marks and
the space character. What you don't want in labels though are tabs, new
line characters or HTML tags. This validator is for such uses.

The given value is valid if it matches the regular expression specified in PATTERN_VALIDCHARACTERS.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: LocaleIdentifierValidator`:

LocaleIdentifierValidator
-------------------------

A validator for locale identifiers.

This validator validates a string based on the expressions of the
Flow I18n implementation.

Is valid if the given value is a valid "locale identifier".

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: NotEmptyValidator`:

NotEmptyValidator
-----------------

Validator for not empty values.

Checks if the given value is not empty (NULL, empty string, empty array
or empty object that implements the Countable interface).




.. _`Flow Validator Reference: NumberRangeValidator`:

NumberRangeValidator
--------------------

Validator for general numbers

The given value is valid if it is a number in the specified range.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``minimum`` (integer, *optional*): The minimum value to accept

* ``maximum`` (integer, *optional*): The maximum value to accept




.. _`Flow Validator Reference: NumberValidator`:

NumberValidator
---------------

Validator for general numbers.

Checks if the given value is a valid number.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``locale`` (string|Locale, *optional*): The locale to use for number parsing

* ``strictMode`` (boolean, *optional*): Use strict mode for number parsing

* ``formatLength`` (string, *optional*): The format length, see NumbersReader::FORMAT_LENGTH_*

* ``formatType`` (string, *optional*): The format type, see NumbersReader::FORMAT_TYPE_*




.. _`Flow Validator Reference: RawValidator`:

RawValidator
------------

A validator which accepts any input.

This validator is always valid.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: RegularExpressionValidator`:

RegularExpressionValidator
--------------------------

Validator based on regular expressions.

Checks if the given value matches the specified regular expression.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``regularExpression`` (string): The regular expression to use for validation, used as given




.. _`Flow Validator Reference: StringLengthValidator`:

StringLengthValidator
---------------------

Validator for string length.

Checks if the given value is a valid string (or can be cast to a string
if an object is given) and its length is between minimum and maximum
specified in the validation options.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``minimum`` (integer, *optional*): Minimum length for a valid string

* ``maximum`` (integer, *optional*): Maximum length for a valid string




.. _`Flow Validator Reference: StringValidator`:

StringValidator
---------------

Validator for strings.

Checks if the given value is a string.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: TextValidator`:

TextValidator
-------------

Validator for "plain" text.

Checks if the given value is a valid text (contains no XML tags).

Be aware that the value of this check entirely depends on the output context.
The validated text is not expected to be secure in every circumstance, if you
want to be sure of that, use a customized regular expression or filter on output.

See http://php.net/filter_var for details.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Flow Validator Reference: UniqueEntityValidator`:

UniqueEntityValidator
---------------------

Validator for uniqueness of entities.

Checks if the given value is a unique entity depending on it's identity properties or
custom configured identity properties.

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``identityProperties`` (array, *optional*): List of custom identity properties.




.. _`Flow Validator Reference: UuidValidator`:

UuidValidator
-------------

Validator for Universally Unique Identifiers.

Checks if the given value is a syntactically valid UUID.

.. note:: A value of NULL or an empty string ('') is considered valid



