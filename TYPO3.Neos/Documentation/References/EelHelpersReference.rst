.. _`Eel Helpers Reference`:

Eel Helpers Reference
=====================

This reference was automatically generated from code on 2016-06-14


.. _`Eel Helpers Reference: Array`:

Array
-----

Array helpers for Eel contexts

The implementation uses the JavaScript specificiation where applicable, including EcmaScript 6 proposals.

See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Array for a documentation and
specification of the JavaScript implementation.

Implemented in: ``TYPO3\Eel\Helper\ArrayHelper``

Array.concat(array1, array2, array\_)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Concatenate arrays or values to a new array

* ``array1`` (array|mixed) First array or value
* ``array2`` (array|mixed) Second array or value
* ``array_`` (array|mixed, *optional*) Optional variable list of additional arrays / values

**Return** (array) The array with concatenated arrays or values

Array.first(array)
^^^^^^^^^^^^^^^^^^

Get the first element of an array

* ``array`` (array) The array

**Return** (mixed)

Array.indexOf(array, searchElement, fromIndex)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``array`` (array)
* ``searchElement`` (mixed)
* ``fromIndex`` (integer, *optional*)

**Return** (mixed)

Array.isEmpty(array)
^^^^^^^^^^^^^^^^^^^^

Check if an array is empty

* ``array`` (array) The array

**Return** (boolean) TRUE if the array is empty

Array.join(array, separator)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Join values of an array with a separator

* ``array`` (array) Array with values to join
* ``separator`` (string, *optional*) A separator for the values

**Return** (string) A string with the joined values separated by the separator

Array.keys(array)
^^^^^^^^^^^^^^^^^

Get the array keys

* ``array`` (array) The array

**Return** (array)

Array.last(array)
^^^^^^^^^^^^^^^^^

Get the last element of an array

* ``array`` (array) The array

**Return** (mixed)

Array.length(array)
^^^^^^^^^^^^^^^^^^^

Get the length of an array

* ``array`` (array) The array

**Return** (integer)

Array.pop(array)
^^^^^^^^^^^^^^^^

Removes the last element from an array

Note: This differs from the JavaScript behavior of Array.pop which will return the popped element.

An empty array will result in an empty array again.

* ``array`` (array)

**Return** (array) The array without the last element

Array.push(array, element)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Insert one or more elements at the end of an array

Allows to push multiple elements at once::

    Array.push(array, e1, e2)

* ``array`` (array)
* ``element`` (mixed)

**Return** (array) The array with the inserted elements

Array.random(array)
^^^^^^^^^^^^^^^^^^^

Picks a random element from the array

* ``array`` (array)

**Return** (mixed) A random entry or NULL if the array is empty

Array.reverse(array)
^^^^^^^^^^^^^^^^^^^^

Returns an array in reverse order

* ``array`` (array) The array

**Return** (array)

Array.shift(array)
^^^^^^^^^^^^^^^^^^

Remove the first element of an array

Note: This differs from the JavaScript behavior of Array.shift which will return the shifted element.

An empty array will result in an empty array again.

* ``array`` (array)

**Return** (array) The array without the first element

Array.shuffle(array, preserveKeys)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shuffle an array

Randomizes entries an array with the option to preserve the existing keys.
When this option is set to FALSE, all keys will be replaced

* ``array`` (array)
* ``preserveKeys`` (boolean, *optional*) Wether to preserve the keys when shuffling the array

**Return** (array) The shuffled array

Array.slice(array, begin, end)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Extract a portion of an indexed array

* ``array`` (array) The array (with numeric indices)
* ``begin`` (string)
* ``end`` (string, *optional*)

**Return** (array)

Array.sort(array)
^^^^^^^^^^^^^^^^^

Sorts an array

The sorting is done first by numbers, then by characters.

Internally natsort() is used as it most closely resembles javascript's sort().
Because there are no real associative arrays in Javascript, keys of the array will be preserved.

* ``array`` (array)

**Return** (array) The sorted array

Array.splice(array, offset, length, replacements)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Replaces a range of an array by the given replacements

Allows to give multiple replacements at once::

    Array.splice(array, 3, 2, 'a', 'b')

* ``array`` (array)
* ``offset`` (integer) Index of the first element to remove
* ``length`` (integer, *optional*) Number of elements to remove
* ``replacements`` (mixed, *optional*) Elements to insert instead of the removed range

**Return** (array) The array with removed and replaced elements

Array.unshift(array, element)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Insert one or more elements at the beginning of an array

Allows to insert multiple elements at once::

    Array.unshift(array, e1, e2)

* ``array`` (array)
* ``element`` (mixed)

**Return** (array) The array with the inserted elements






.. _`Eel Helpers Reference: Configuration`:

Configuration
-------------

Configuration helpers for Eel contexts

Implemented in: ``TYPO3\Eel\Helper\ConfigurationHelper``

Configuration.setting(settingPath)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Return the specified settings

Examples::

    Configuration.setting('TYPO3.Flow.core.context') == 'Production'

    Configuration.setting('Acme.Demo.speedMode') == 'light speed'

* ``settingPath`` (string)

**Return** (mixed)






.. _`Eel Helpers Reference: Date`:

Date
----

Date helpers for Eel contexts

Implemented in: ``TYPO3\Eel\Helper\DateHelper``

Date.add(date, interval)
^^^^^^^^^^^^^^^^^^^^^^^^

Add an interval to a date and return a new DateTime object

* ``date`` (\DateTime)
* ``interval`` (string|\DateInterval)

**Return** (\DateTime)

Date.dayOfMonth(dateTime)
^^^^^^^^^^^^^^^^^^^^^^^^^

Get the day of month of a date

* ``dateTime`` (\DateTime)

**Return** (integer) The day of month of the given date

Date.diff(dateA, dateB)
^^^^^^^^^^^^^^^^^^^^^^^

Get the difference between two dates as a \DateInterval object

* ``dateA`` (\DateTime)
* ``dateB`` (\DateTime)

**Return** (\DateInterval)

Date.format(date, format)
^^^^^^^^^^^^^^^^^^^^^^^^^

Format a date (or interval) to a string with a given format

See formatting options as in PHP date()

* ``date`` (integer|string|\DateTime|\DateInterval)
* ``format`` (string)

**Return** (string)

Date.hour(dateTime)
^^^^^^^^^^^^^^^^^^^

Get the hour of a date (24 hour format)

* ``dateTime`` (\DateTime)

**Return** (integer) The hour of the given date

Date.minute(dateTime)
^^^^^^^^^^^^^^^^^^^^^

Get the minute of a date

* ``dateTime`` (\DateTime)

**Return** (integer) The minute of the given date

Date.month(dateTime)
^^^^^^^^^^^^^^^^^^^^

Get the month of a date

* ``dateTime`` (\DateTime)

**Return** (integer) The month of the given date

Date.now()
^^^^^^^^^^

Get the current date and time

Examples::

    Date.now().timestamp

**Return** (\DateTime)

Date.parse(string, format)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Parse a date from string with a format to a DateTime object

* ``string`` (string)
* ``format`` (string)

**Return** (\DateTime)

Date.second(dateTime)
^^^^^^^^^^^^^^^^^^^^^

Get the second of a date

* ``dateTime`` (\DateTime)

**Return** (integer) The second of the given date

Date.subtract(date, interval)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Subtract an interval from a date and return a new DateTime object

* ``date`` (\DateTime)
* ``interval`` (string|\DateInterval)

**Return** (\DateTime)

Date.today()
^^^^^^^^^^^^

Get the current date

**Return** (\DateTime)

Date.year(dateTime)
^^^^^^^^^^^^^^^^^^^

Get the year of a date

* ``dateTime`` (\DateTime)

**Return** (integer) The year of the given date






.. _`Eel Helpers Reference: Json`:

Json
----

JSON helpers for Eel contexts

Implemented in: ``TYPO3\Eel\Helper\JsonHelper``

Json.parse(json, associativeArrays)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

JSON decode the given string

* ``json`` (string)
* ``associativeArrays`` (boolean, *optional*)

**Return** (mixed)

Json.stringify(value)
^^^^^^^^^^^^^^^^^^^^^

JSON encode the given value

* ``value`` (mixed)

**Return** (string)






.. _`Eel Helpers Reference: Math`:

Math
----

Math helpers for Eel contexts

The implementation sticks to the JavaScript specificiation including EcmaScript 6 proposals.

See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Math for a documentation and
specification of the JavaScript implementation.

Implemented in: ``TYPO3\Eel\Helper\MathHelper``

Math.abs(x)
^^^^^^^^^^^

* ``x`` (float, *optional*) A number

**Return** (float) The absolute value of the given value

Math.acos(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The arccosine (in radians) of the given value

Math.acosh(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic arccosine (in radians) of the given value

Math.asin(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The arcsine (in radians) of the given value

Math.asinh(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic arcsine (in radians) of the given value

Math.atan(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The arctangent (in radians) of the given value

Math.atan2(y, x)
^^^^^^^^^^^^^^^^

* ``y`` (float) A number
* ``x`` (float) A number

**Return** (float) The arctangent of the quotient of its arguments

Math.atanh(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic arctangent (in radians) of the given value

Math.cbrt(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The cube root of the given value

Math.ceil(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The smallest integer greater than or equal to the given value

Math.cos(x)
^^^^^^^^^^^

* ``x`` (float) A number given in radians

**Return** (float) The cosine of the given value

Math.cosh(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic cosine of the given value

Math.exp(x)
^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The power of the Euler's constant with the given value (e^x)

Math.expm1(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The power of the Euler's constant with the given value minus 1 (e^x - 1)

Math.floor(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The largest integer less than or equal to the given value

Math.getE()
^^^^^^^^^^^

**Return** (float) Euler's constant and the base of natural logarithms, approximately 2.718

Math.getLN10()
^^^^^^^^^^^^^^

**Return** (float) Natural logarithm of 10, approximately 2.303

Math.getLN2()
^^^^^^^^^^^^^

**Return** (float) Natural logarithm of 2, approximately 0.693

Math.getLOG10E()
^^^^^^^^^^^^^^^^

**Return** (float) Base 10 logarithm of E, approximately 0.434

Math.getLOG2E()
^^^^^^^^^^^^^^^

**Return** (float) Base 2 logarithm of E, approximately 1.443

Math.getPI()
^^^^^^^^^^^^

**Return** (float) Ratio of the circumference of a circle to its diameter, approximately 3.14159

Math.getSQRT1\_2()
^^^^^^^^^^^^^^^^^^

**Return** (float) Square root of 1/2; equivalently, 1 over the square root of 2, approximately 0.707

Math.getSQRT2()
^^^^^^^^^^^^^^^

**Return** (float) Square root of 2, approximately 1.414

Math.hypot(x, y, z\_)
^^^^^^^^^^^^^^^^^^^^^

* ``x`` (float) A number
* ``y`` (float) A number
* ``z_`` (float, *optional*) Optional variable list of additional numbers

**Return** (float) The square root of the sum of squares of the arguments

Math.isFinite(x)
^^^^^^^^^^^^^^^^

Test if the given value is a finite number

This is equivalent to the global isFinite() function in JavaScript.

* ``x`` (mixed) A value

**Return** (boolean) TRUE if the value is a finite (not NAN) number

Math.isInfinite(x)
^^^^^^^^^^^^^^^^^^

Test if the given value is an infinite number (INF or -INF)

This function has no direct equivalent in JavaScript.

* ``x`` (mixed) A value

**Return** (boolean) TRUE if the value is INF or -INF

Math.isNaN(x)
^^^^^^^^^^^^^

Test if the given value is not a number (either not numeric or NAN)

This is equivalent to the global isNaN() function in JavaScript.

* ``x`` (mixed) A value

**Return** (boolean) TRUE if the value is not a number

Math.log(x)
^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The natural logarithm (base e) of the given value

Math.log10(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The base 10 logarithm of the given value

Math.log1p(x)
^^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The natural logarithm (base e) of 1 + the given value

Math.log2(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The base 2 logarithm of the given value

Math.max(x, y\_)
^^^^^^^^^^^^^^^^

* ``x`` (float, *optional*) A number
* ``y_`` (float, *optional*) Optional variable list of additional numbers

**Return** (float) The largest of the given numbers (zero or more)

Math.min(x, y\_)
^^^^^^^^^^^^^^^^

* ``x`` (float, *optional*) A number
* ``y_`` (float, *optional*) Optional variable list of additional numbers

**Return** (float) The smallest of the given numbers (zero or more)

Math.pow(x, y)
^^^^^^^^^^^^^^

Calculate the power of x by y

* ``x`` (float) The base
* ``y`` (float) The exponent

**Return** (float) The base to the exponent power (x^y)

Math.random()
^^^^^^^^^^^^^

Get a random foating point number between 0 (inclusive) and 1 (exclusive)

That means a result will always be less than 1 and greater or equal to 0, the same way Math.random() works in
JavaScript.

See Math.randomInt(min, max) for a function that returns random integer numbers from a given interval.

**Return** (float) A random floating point number between 0 (inclusive) and 1 (exclusive), that is from [0, 1)

Math.randomInt(min, max)
^^^^^^^^^^^^^^^^^^^^^^^^

Get a random integer number between a min and max value (inclusive)

That means a result will always be greater than or equal to min and less than or equal to max.

* ``min`` (integer) The lower bound for the random number (inclusive)
* ``max`` (integer) The upper bound for the random number (inclusive)

**Return** (integer) A random number between min and max (inclusive), that is from [min, max]

Math.round(subject, precision)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Rounds the subject to the given precision

The precision defines the number of digits after the decimal point.
Negative values are also supported (-1 rounds to full 10ths).

* ``subject`` (float) The value to round
* ``precision`` (integer, *optional*) The precision (digits after decimal point) to use, defaults to 0

**Return** (float) The rounded value

Math.sign(x)
^^^^^^^^^^^^

Get the sign of the given number, indicating whether the number is positive, negative or zero

* ``x`` (integer|float) The value

**Return** (integer) -1, 0, 1 depending on the sign or NAN if the given value was not numeric

Math.sin(x)
^^^^^^^^^^^

* ``x`` (float) A number given in radians

**Return** (float) The sine of the given value

Math.sinh(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic sine of the given value

Math.sqrt(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The square root of the given number

Math.tan(x)
^^^^^^^^^^^

* ``x`` (float) A number given in radians

**Return** (float) The tangent of the given value

Math.tanh(x)
^^^^^^^^^^^^

* ``x`` (float) A number

**Return** (float) The hyperbolic tangent of the given value

Math.trunc(x)
^^^^^^^^^^^^^

Get the integral part of the given number by removing any fractional digits

This function doesn't round the given number but merely calls ceil(x) or floor(x) depending
on the sign of the number.

* ``x`` (float) A number

**Return** (integer) The integral part of the given number






.. _`Eel Helpers Reference: Neos.Array`:

Neos.Array
----------

Some Functional Programming Array helpers for Eel contexts

These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*

Implemented in: ``TYPO3\Neos\TypoScript\Helper\ArrayHelper``

Neos.Array.filter(set, filterProperty)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to TRUE.

* ``set`` (array|Collection)
* ``filterProperty`` (string)

**Return** (array)

Neos.Array.filterNegated(set, filterProperty)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to FALSE.

* ``set`` (array|Collection)
* ``filterProperty`` (string)

**Return** (array)

Neos.Array.groupBy(set, groupingKey)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The input is assumed to be an array or Collection of objects. Groups this input by the $groupingKey property of each element.

* ``set`` (array|Collection)
* ``groupingKey`` (string)

**Return** (array)






.. _`Eel Helpers Reference: Neos.Caching`:

Neos.Caching
------------

Caching helper to make cache tag generation easier.

Implemented in: ``TYPO3\Neos\TypoScript\Helper\CachingHelper``

Neos.Caching.descendantOfTag(nodes)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Generate a `@cache` entry tag for descendants of a node, an array of nodes or a FlowQuery result
A cache entry with this tag will be flushed whenever a node
(for any variant) that is a descendant (child on any level) of one of
the given nodes is updated.

* ``nodes`` (mixed) (A single Node or array or \Traversable of Nodes)

**Return** (array)

Neos.Caching.nodeTag(nodes)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Generate a `@cache` entry tag for a single node, array of nodes or a FlowQuery result
A cache entry with this tag will be flushed whenever one of the
given nodes (for any variant) is updated.

* ``nodes`` (mixed) (A single Node or array or \Traversable of Nodes)

**Return** (array)

Neos.Caching.nodeTypeTag(nodeType)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Generate an `@cache` entry tag for a node type
A cache entry with this tag will be flushed whenever a node
(for any variant) that is of the given node type (including inheritance)
is updated.

* ``nodeType`` (NodeType)

**Return** (string)






.. _`Eel Helpers Reference: Neos.Link`:

Neos.Link
---------

Eel helper for the linking service

Implemented in: ``TYPO3\Neos\TypoScript\Helper\LinkHelper``

Neos.Link.convertUriToObject(uri, contextNode)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|Uri)
* ``contextNode`` (NodeInterface, *optional*)

**Return** (NodeInterface|AssetInterface|NULL)

Neos.Link.getScheme(uri)
^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|Uri)

**Return** (string)

Neos.Link.hasSupportedScheme(uri)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|Uri)

**Return** (boolean)

Neos.Link.resolveAssetUri(uri)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|Uri)

**Return** (string)

Neos.Link.resolveNodeUri(uri, contextNode, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|Uri)
* ``contextNode`` (NodeInterface)
* ``controllerContext`` (ControllerContext)

**Return** (string)






.. _`Eel Helpers Reference: Neos.Node`:

Neos.Node
---------

Eel helper for TYPO3CR Nodes

Implemented in: ``TYPO3\Neos\TypoScript\Helper\NodeHelper``

Neos.Node.nearestContentCollection(node, nodePath)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
if no content collection could be found

* ``node`` (NodeInterface)
* ``nodePath`` (string)

**Return** (NodeInterface)






.. _`Eel Helpers Reference: Neos.Rendering`:

Neos.Rendering
--------------

Render Content Dimension Names, Node Labels

These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*

Implemented in: ``TYPO3\Neos\TypoScript\Helper\RenderingHelper``

Neos.Rendering.injectConfigurationManager(configurationManager)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``configurationManager`` (ConfigurationManager)

**Return** (void)

Neos.Rendering.labelForNodeType(nodeTypeName)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Render the label for the given $nodeTypeName

* ``nodeTypeName`` (string)

**Return** (string)

Neos.Rendering.renderDimensions(dimensions)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Render a human-readable description for the passed $dimensions

* ``dimensions`` (array)

**Return** (string)






.. _`Eel Helpers Reference: Security`:

Security
--------

Helper for security related information

Implemented in: ``TYPO3\Eel\Helper\SecurityHelper``

Security.getAccount()
^^^^^^^^^^^^^^^^^^^^^

Get the account of the first authenticated token.

**Return** (\TYPO3\Flow\Security\Account|NULL)






.. _`Eel Helpers Reference: String`:

String
------

String helpers for Eel contexts

Implemented in: ``TYPO3\Eel\Helper\StringHelper``

String.charAt(string, index)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the character at a specific position

Example::

    String.charAt("abcdefg", 5) == "f"

* ``string`` (string) The input string
* ``index`` (integer) The index to get

**Return** (string) The character at the given index

String.crop(string, maximumCharacters, suffix)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Crop a string to ``maximumCharacters`` length, optionally appending ``suffix`` if cropping was necessary.

* ``string`` (string) The input string
* ``maximumCharacters`` (integer) Number of characters where cropping should happen
* ``suffix`` (string, *optional*) Suffix to be appended if cropping was necessary

**Return** (string) The cropped string

String.cropAtSentence(string, maximumCharacters, suffix)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Crop a string to ``maximumCharacters`` length, taking sentences into account,
optionally appending ``suffix`` if cropping was necessary.

* ``string`` (string) The input string
* ``maximumCharacters`` (integer) Number of characters where cropping should happen
* ``suffix`` (string, *optional*) Suffix to be appended if cropping was necessary

**Return** (string) The cropped string

String.cropAtWord(string, maximumCharacters, suffix)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Crop a string to ``maximumCharacters`` length, taking words into account,
optionally appending ``suffix`` if cropping was necessary.

* ``string`` (string) The input string
* ``maximumCharacters`` (integer) Number of characters where cropping should happen
* ``suffix`` (string, *optional*) Suffix to be appended if cropping was necessary

**Return** (string) The cropped string

String.endsWith(string, search, position)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Test if a string ends with the given search string

Example::

    String.endsWith('Hello, World!', 'World!') == true

* ``string`` (string) The string
* ``search`` (string) A string to search
* ``position`` (integer, *optional*) Optional position for limiting the string

**Return** (boolean) TRUE if the string ends with the given search

String.firstLetterToLowerCase(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Lowercase the first letter of a string

Example::

    String.firstLetterToLowerCase('CamelCase') == 'camelCase'

* ``string`` (string) The input string

**Return** (string) The string with the first letter in lowercase

String.firstLetterToUpperCase(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Uppercase the first letter of a string

Example::

    String.firstLetterToUpperCase('hello world') == 'Hello world'

* ``string`` (string) The input string

**Return** (string) The string with the first letter in uppercase

String.htmlSpecialChars(string, preserveEntities)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Convert special characters to HTML entities

* ``string`` (string) The string to convert
* ``preserveEntities`` (boolean, *optional*) ``true`` if entities should not be double encoded

**Return** (string) The converted string

String.indexOf(string, search, fromIndex)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Find the first position of a substring in the given string

Example::

    String.indexOf("Blue Whale", "Blue") == 0

* ``string`` (string) The input string
* ``search`` (string) The substring to search for
* ``fromIndex`` (integer, *optional*) The index where the search should start, defaults to the beginning

**Return** (integer) The index of the substring (>= 0) or -1 if the substring was not found

String.isBlank(string)
^^^^^^^^^^^^^^^^^^^^^^

Test if the given string is blank (empty or consists of whitespace only)

Examples::

    String.isBlank('') == true
    String.isBlank('  ') == true

* ``string`` (string) The string to test

**Return** (boolean) ``true`` if the given string is blank

String.lastIndexOf(string, search, toIndex)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Find the last position of a substring in the given string

Example::

    String.lastIndexOf("Developers Developers Developers!", "Developers") == 22

* ``string`` (string) The input string
* ``search`` (string) The substring to search for
* ``toIndex`` (integer, *optional*) The position where the backwards search should start, defaults to the end

**Return** (integer) The last index of the substring (>=0) or -1 if the substring was not found

String.length(string)
^^^^^^^^^^^^^^^^^^^^^

Get the length of a string

* ``string`` (string) The input string

**Return** (integer) Length of the string

String.md5(string)
^^^^^^^^^^^^^^^^^^

Calculate the MD5 checksum of the given string

* ``string`` (string) The string to hash

**Return** (string) The MD5 hash of ``string``

String.pregMatch(string, pattern)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Match a string with a regular expression (PREG style)

* ``string`` (string)
* ``pattern`` (string)

**Return** (array) The matches as array or NULL if not matched

String.pregReplace(string, pattern, replace)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Replace occurrences of a search string inside the string using regular expression matching (PREG style)

* ``string`` (string)
* ``pattern`` (string)
* ``replace`` (string)

**Return** (string) The string with all occurrences replaced

String.rawUrlDecode(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Decode the string from URLs according to RFC 3986

* ``string`` (string) The string to decode

**Return** (string) The decoded string

String.rawUrlEncode(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Encode the string for URLs according to RFC 3986

* ``string`` (string) The string to encode

**Return** (string) The encoded string

String.replace(string, search, replace)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Replace occurrences of a search string inside the string

Note: this method does not perform regular expression matching, @see pregReplace().

* ``string`` (string)
* ``search`` (string)
* ``replace`` (string)

**Return** (string) The string with all occurrences replaced

String.split(string, separator, limit)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Split a string by a separator

Node: This implementation follows JavaScript semantics without support of regular expressions.

* ``string`` (string) The string to split
* ``separator`` (string, *optional*) The separator where the string should be splitted
* ``limit`` (integer, *optional*) The maximum amount of items to split

**Return** (array) An array of the splitted parts, excluding the separators

String.startsWith(string, search, position)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Test if a string starts with the given search string

Examples::

    String.startsWith('Hello world!', 'Hello') == true
    String.startsWith('My hovercraft is full of...', 'Hello') == false
    String.startsWith('My hovercraft is full of...', 'hovercraft', 3) == true

* ``string`` (string) The input string
* ``search`` (string) The string to search for
* ``position`` (integer, *optional*) The position to test (defaults to the beginning of the string)

**Return** (boolean)

String.stripTags(string)
^^^^^^^^^^^^^^^^^^^^^^^^

Strip all HTML tags from the given string

Example::

    String.stripTags('<a href="#">Some link</a>') == 'Some link'

This is a wrapper for the strip_tags() PHP function.

* ``string`` (string) The string to strip

**Return** (string) The string with tags stripped

String.substr(string, start, length)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Return the characters in a string from start up to the given length

This implementation follows the JavaScript specification for "substr".

Examples::

    String.substr('Hello, World!', 7, 5) == 'World'
    String.substr('Hello, World!', 7) == 'World!'
    String.substr('Hello, World!', -6) == 'World!'

* ``string`` (string) A string
* ``start`` (integer) Start offset
* ``length`` (integer, *optional*) Maximum length of the substring that is returned

**Return** (string) The substring

String.substring(string, start, end)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Return the characters in a string from a start index to an end index

This implementation follows the JavaScript specification for "substring".

Examples::

    String.substring('Hello, World!', 7, 12) == 'World'
    String.substring('Hello, World!', 7) == 'World!'

* ``string`` (string)
* ``start`` (integer) Start index
* ``end`` (integer, *optional*) End index

**Return** (string) The substring

String.toBoolean(string)
^^^^^^^^^^^^^^^^^^^^^^^^

Convert a string to boolean

A value is ``true``, if it is either the string ``"TRUE"`` or ``"true"`` or the number ``1``.

* ``string`` (string) The string to convert

**Return** (boolean) The boolean value of the string (``true`` or ``false``)

String.toFloat(string)
^^^^^^^^^^^^^^^^^^^^^^

Convert a string to float

* ``string`` (string) The string to convert

**Return** (float) The float value of the string

String.toInteger(string)
^^^^^^^^^^^^^^^^^^^^^^^^

Convert a string to integer

* ``string`` (string) The string to convert

**Return** (integer) The converted string

String.toLowerCase(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Lowercase a string

* ``string`` (string) The input string

**Return** (string) The string in lowercase

String.toString(value)
^^^^^^^^^^^^^^^^^^^^^^

Convert the given value to a string

* ``value`` (mixed) The value to convert (must be convertible to string)

**Return** (string) The string value

String.toUpperCase(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Uppercase a string

* ``string`` (string) The input string

**Return** (string) The string in uppercase

String.trim(string, charlist)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Trim whitespace at the beginning and end of a string

* ``string`` (string) The string to trim
* ``charlist`` (string, *optional*) List of characters that should be trimmed, defaults to whitespace

**Return** (string) The trimmed string






.. _`Eel Helpers Reference: Translation`:

Translation
-----------

Translation helpers for Eel contexts

Implemented in: ``TYPO3\Flow\I18n\EelHelper\TranslationHelper``

Translation.id(id)
^^^^^^^^^^^^^^^^^^

Start collection of parameters for translation by id

* ``id`` (string) Id to use for finding translation (trans-unit id in XLIFF)

**Return** (TranslationParameterToken)

Translation.translate(id, originalLabel, arguments, source, package, quantity, locale)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the translated value for an id or original label

If only id is set and contains a translation shorthand string, translate
according to that shorthand

In all other cases:

Replace all placeholders with corresponding values if they exist in the
translated label.

* ``id`` (string) Id to use for finding translation (trans-unit id in XLIFF)
* ``originalLabel`` (string, *optional*) The original translation value (the untranslated source string).
* ``arguments`` (array, *optional*) Numerically indexed array of values to be inserted into placeholders
* ``source`` (string, *optional*) Name of file with translations
* ``package`` (string, *optional*) Target package key. If not set, the current package key will be used
* ``quantity`` (mixed, *optional*) A number to find plural form for (float or int), NULL to not use plural forms
* ``locale`` (string, *optional*) An identifier of locale to use (NULL for use the default locale)

**Return** (string) Translated label or source label / ID key

Translation.value(value)
^^^^^^^^^^^^^^^^^^^^^^^^

Start collection of parameters for translation by original label

* ``value`` (string)

**Return** (TranslationParameterToken)






.. _`Eel Helpers Reference: Type`:

Type
----

Type helper for Eel contexts

Implemented in: ``TYPO3\Eel\Helper\TypeHelper``

Type.className(variable)
^^^^^^^^^^^^^^^^^^^^^^^^

Get the class name of the given variable or NULL if it wasn't an object

* ``variable`` (object)

**Return** (string|NULL)

Type.getType(variable)
^^^^^^^^^^^^^^^^^^^^^^

Get the variable type

* ``variable`` (mixed)

**Return** (string)

Type.instance(variable, expectedObjectType)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable of the provided object type.

* ``variable`` (mixed)
* ``expectedObjectType`` (string)

**Return** (boolean)

Type.isArray(variable)
^^^^^^^^^^^^^^^^^^^^^^

Is the given variable an array.

* ``variable`` (mixed)

**Return** (boolean)

Type.isBoolean(variable)
^^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable boolean.

* ``variable`` (mixed)

**Return** (boolean)

Type.isFloat(variable)
^^^^^^^^^^^^^^^^^^^^^^

Is the given variable a float.

* ``variable`` (mixed)

**Return** (boolean)

Type.isInteger(variable)
^^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable an integer.

* ``variable`` (mixed)

**Return** (boolean)

Type.isNumeric(variable)
^^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable numeric.

* ``variable`` (mixed)

**Return** (boolean)

Type.isObject(variable)
^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable an object.

* ``variable`` (mixed)

**Return** (boolean)

Type.isScalar(variable)
^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable a scalar.

* ``variable`` (mixed)

**Return** (boolean)

Type.isString(variable)
^^^^^^^^^^^^^^^^^^^^^^^

Is the given variable a string.

* ``variable`` (mixed)

**Return** (boolean)

Type.typeof(variable)
^^^^^^^^^^^^^^^^^^^^^

Get the variable type

* ``variable`` (mixed)

**Return** (string)





