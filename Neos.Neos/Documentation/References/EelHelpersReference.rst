.. _`Eel Helpers Reference`:

Eel Helpers Reference
=====================

This reference was automatically generated from code on 2020-12-10


.. _`Eel Helpers Reference: Api`:

Api
---



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\ApiHelper``

Api.emptyArrayToObject(array)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Converts an empty array to an empty object. Does nothing if array is not empty.

Use this helper to prevent associative arrays from being converted to non-associative arrays by json_encode.
This is an internal helper and might change without further notice
FIXME: Probably better to produce objects in the first place "upstream".

* ``array`` (array) Associative array which may be empty

**Return** (array|\stdClass) Non-empty associative array or empty object






.. _`Eel Helpers Reference: Array`:

Array
-----

Array helpers for Eel contexts

The implementation uses the JavaScript specificiation where applicable, including EcmaScript 6 proposals.

See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Array for a documentation and
specification of the JavaScript implementation.

Implemented in: ``Neos\Eel\Helper\ArrayHelper``

Array.concat(array1, array2, array\_)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Concatenate arrays or values to a new array

* ``array1`` (iterable|mixed) First array or value
* ``array2`` (iterable|mixed) Second array or value
* ``array_`` (iterable|mixed, *optional*) Optional variable list of additional arrays / values

**Return** (array) The array with concatenated arrays or values

Array.every(array, callback)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Check if all elements in an array pass a test given by the calback,
passing each element and key as arguments

Example::

    Array.every([1, 2, 3, 4], x => x % 2 == 0) // == false
    Array.every([2, 4, 6, 8], x => x % 2) // == true

* ``array`` (iterable) Array of elements to test
* ``callback`` (callable) Callback for testing elements, current value and key will be passed as arguments

**Return** (bool) True if all elements passed the test

Array.filter(array, callback)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Filter an array by a test given as the callback, passing each element and key as arguments

Examples:

    Array.filter([1, 2, 3, 4], x => x % 2 == 0) // == [2, 4]
    Array.filter(['foo', 'bar', 'baz'], (x, index) => index < 2) // == ['foo', 'bar']

* ``array`` (iterable) Array of elements to filter
* ``callback`` (callable, *optional*) Callback for testing if an element should be included in the result, current value and key will be passed as arguments

**Return** (array) The array with elements where callback returned true

Array.first(array)
^^^^^^^^^^^^^^^^^^

Get the first element of an array

* ``array`` (iterable) The array

**Return** (mixed)

Array.flip(array)
^^^^^^^^^^^^^^^^^

Exchanges all keys with their associated values in an array

Note that the values of array need to be valid keys, i.e. they need to be either int or string.
If a value has several occurrences, the latest key will be used as its value, and all others will be lost.

* ``array`` (iterable)

**Return** (array) The array with flipped keys and values

Array.indexOf(array, searchElement, fromIndex)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Returns the first index at which a given element can be found in the array,
or -1 if it is not present

* ``array`` (iterable) The array
* ``searchElement`` (mixed) The element value to find
* ``fromIndex`` (int, *optional*) Position in the array to start the search.

**Return** (int)

Array.isEmpty(array)
^^^^^^^^^^^^^^^^^^^^

Check if an array is empty

* ``array`` (iterable) The array

**Return** (bool) true if the array is empty

Array.join(array, separator)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Join values of an array with a separator

* ``array`` (iterable) Array with values to join
* ``separator`` (string, *optional*) A separator for the values

**Return** (string) A string with the joined values separated by the separator

Array.keys(array)
^^^^^^^^^^^^^^^^^

Get the array keys

* ``array`` (iterable) The array

**Return** (array)

Array.ksort(array)
^^^^^^^^^^^^^^^^^^

Sort an array by key

* ``array`` (iterable) The array to sort

**Return** (array) The sorted array

Array.last(array)
^^^^^^^^^^^^^^^^^

Get the last element of an array

* ``array`` (iterable) The array

**Return** (mixed)

Array.length(array)
^^^^^^^^^^^^^^^^^^^

Get the length of an array

* ``array`` (iterable) The array

**Return** (int)

Array.map(array, callback)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Apply the callback to each element of the array, passing each element and key as arguments

Examples::

    Array.map([1, 2, 3, 4], x => x * x)
    Array.map([1, 2, 3, 4], (x, index) => x * index)

* ``array`` (iterable) Array of elements to map
* ``callback`` (callable) Callback to apply for each element, current value and key will be passed as arguments

**Return** (array) The array with callback applied, keys will be preserved

Array.pop(array)
^^^^^^^^^^^^^^^^

Removes the last element from an array

Note: This differs from the JavaScript behavior of Array.pop which will return the popped element.

An empty array will result in an empty array again.

* ``array`` (iterable)

**Return** (array) The array without the last element

Array.push(array, element)
^^^^^^^^^^^^^^^^^^^^^^^^^^

Insert one or more elements at the end of an array

Allows to push multiple elements at once::

    Array.push(array, e1, e2)

* ``array`` (iterable)
* ``element`` (mixed)

**Return** (array) The array with the inserted elements

Array.random(array)
^^^^^^^^^^^^^^^^^^^

Picks a random element from the array

* ``array`` (array)

**Return** (mixed) A random entry or null if the array is empty

Array.range(start, end, step)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create an array containing a range of elements

If a step value is given, it will be used as the increment between elements in the sequence.
step should be given as a positive number. If not specified, step will default to 1.

* ``start`` (mixed) First value of the sequence.
* ``end`` (mixed) The sequence is ended upon reaching the end value.
* ``step`` (int, *optional*) The increment between items, will default to 1.

**Return** (array) Array of elements from start to end, inclusive.

Array.reduce(array, callback, initialValue)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Apply the callback to each element of the array and accumulate a single value

Examples::

    Array.reduce([1, 2, 3, 4], (accumulator, currentValue) => accumulator + currentValue) // == 10
    Array.reduce([1, 2, 3, 4], (accumulator, currentValue) => accumulator + currentValue, 1) // == 11

* ``array`` (iterable) Array of elements to reduce to a value
* ``callback`` (callable) Callback for accumulating values, accumulator, current value and key will be passed as arguments
* ``initialValue`` (mixed, *optional*) Initial value, defaults to first item in array and callback starts with second entry

**Return** (mixed)

Array.reverse(array)
^^^^^^^^^^^^^^^^^^^^

Returns an array in reverse order

* ``array`` (iterable) The array

**Return** (array)

Array.set(array, key, value)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Set the specified key in the the array

* ``array`` (iterable)
* ``key`` (string|integer) the key that should be set
* ``value`` (mixed) the value to assign to the key

**Return** (array) The modified array.

Array.shift(array)
^^^^^^^^^^^^^^^^^^

Remove the first element of an array

Note: This differs from the JavaScript behavior of Array.shift which will return the shifted element.

An empty array will result in an empty array again.

* ``array`` (iterable)

**Return** (array) The array without the first element

Array.shuffle(array, preserveKeys)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shuffle an array

Randomizes entries an array with the option to preserve the existing keys.
When this option is set to false, all keys will be replaced

* ``array`` (iterable)
* ``preserveKeys`` (bool, *optional*) Wether to preserve the keys when shuffling the array

**Return** (array) The shuffled array

Array.slice(array, begin, end)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Extract a portion of an indexed array

* ``array`` (iterable) The array (with numeric indices)
* ``begin`` (int)
* ``end`` (int, *optional*)

**Return** (array)

Array.some(array, callback)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Check if at least one element in an array passes a test given by the calback,
passing each element and key as arguments

Example::

    Array.some([1, 2, 3, 4], x => x % 2 == 0) // == true
    Array.some([1, 2, 3, 4], x => x > 4) // == false

* ``array`` (iterable) Array of elements to test
* ``callback`` (callable) Callback for testing elements, current value and key will be passed as arguments

**Return** (bool) True if at least one element passed the test

Array.sort(array)
^^^^^^^^^^^^^^^^^

Sorts an array

The sorting is done first by numbers, then by characters.

Internally natsort() is used as it most closely resembles javascript's sort().
Because there are no real associative arrays in Javascript, keys of the array will be preserved.

* ``array`` (iterable)

**Return** (array) The sorted array

Array.splice(array, offset, length, replacements)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Replaces a range of an array by the given replacements

Allows to give multiple replacements at once::

    Array.splice(array, 3, 2, 'a', 'b')

* ``array`` (iterable)
* ``offset`` (int) Index of the first element to remove
* ``length`` (int, *optional*) Number of elements to remove
* ``replacements`` (mixed, *optional*) Elements to insert instead of the removed range

**Return** (array) The array with removed and replaced elements

Array.unique(array)
^^^^^^^^^^^^^^^^^^^

Removes duplicate values from an array

* ``array`` (iterable) The input array

**Return** (array) The filtered array.

Array.unshift(array, element)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Insert one or more elements at the beginning of an array

Allows to insert multiple elements at once::

    Array.unshift(array, e1, e2)

* ``array`` (iterable)
* ``element`` (mixed)

**Return** (array) The array with the inserted elements






.. _`Eel Helpers Reference: BaseUri`:

BaseUri
-------

This is a purely internal helper to provide baseUris for Caching.
It will be moved to a more sensible package in the future so do
not rely on the classname for now.

Implemented in: ``Neos\Fusion\Eel\BaseUriHelper``

BaseUri.getConfiguredBaseUriOrFallbackToCurrentRequest(fallbackRequest)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``fallbackRequest`` (ServerRequestInterface|null, *optional*)

**Return** (UriInterface)






.. _`Eel Helpers Reference: Configuration`:

Configuration
-------------

Configuration helpers for Eel contexts

Implemented in: ``Neos\Eel\Helper\ConfigurationHelper``

Configuration.setting(settingPath)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Return the specified settings

Examples::

    Configuration.setting('Neos.Flow.core.context') == 'Production'

    Configuration.setting('Acme.Demo.speedMode') == 'light speed'

* ``settingPath`` (string)

**Return** (mixed)






.. _`Eel Helpers Reference: ContentDimensions`:

ContentDimensions
-----------------



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\ContentDimensionsHelper``

ContentDimensions.allowedPresetsByName(dimensions)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``dimensions`` (array) Dimension values indexed by dimension name

**Return** (array) Allowed preset names for the given dimension combination indexed by dimension name

ContentDimensions.contentDimensionsByName()
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

**Return** (array) Dimensions indexed by name with presets indexed by name






.. _`Eel Helpers Reference: Date`:

Date
----

Date helpers for Eel contexts

Implemented in: ``Neos\Eel\Helper\DateHelper``

Date.add(date, interval)
^^^^^^^^^^^^^^^^^^^^^^^^

Add an interval to a date and return a new DateTime object

* ``date`` (\DateTime)
* ``interval`` (string|\DateInterval)

**Return** (\DateTime)

Date.create(time)
^^^^^^^^^^^^^^^^^

Get a date object by given date or time format

Examples::

    Date.create('2018-12-04')
    Date.create('first day of next year')

* ``time`` (String) A date/time string. For valid formats see http://php.net/manual/en/datetime.formats.php

**Return** (\DateTime)

Date.dayOfMonth(dateTime)
^^^^^^^^^^^^^^^^^^^^^^^^^

Get the day of month of a date

* ``dateTime`` (\DateTimeInterface)

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

Date.formatCldr(date, cldrFormat, locale)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Format a date to a string with a given cldr format

* ``date`` (integer|string|\DateTime)
* ``cldrFormat`` (string) Format string in CLDR format (see http://cldr.unicode.org/translation/date-time)
* ``locale`` (null|string, *optional*) String locale - example (de|en|ru_RU)

**Return** (string)

Date.hour(dateTime)
^^^^^^^^^^^^^^^^^^^

Get the hour of a date (24 hour format)

* ``dateTime`` (\DateTimeInterface)

**Return** (integer) The hour of the given date

Date.minute(dateTime)
^^^^^^^^^^^^^^^^^^^^^

Get the minute of a date

* ``dateTime`` (\DateTimeInterface)

**Return** (integer) The minute of the given date

Date.month(dateTime)
^^^^^^^^^^^^^^^^^^^^

Get the month of a date

* ``dateTime`` (\DateTimeInterface)

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

* ``dateTime`` (\DateTimeInterface)

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

* ``dateTime`` (\DateTimeInterface)

**Return** (integer) The year of the given date






.. _`Eel Helpers Reference: File`:

File
----

Helper to read files.

Implemented in: ``Neos\Eel\Helper\FileHelper``

File.exists(filepath)
^^^^^^^^^^^^^^^^^^^^^

Check if the given file path exists

* ``filepath`` (string)

**Return** (bool)

File.fileInfo(filepath)
^^^^^^^^^^^^^^^^^^^^^^^

Get file name and path information

* ``filepath`` (string)

**Return** (array) with keys dirname, basename, extension (if any), and filename

File.getSha1(filepath)
^^^^^^^^^^^^^^^^^^^^^^

* ``filepath`` (string)

**Return** (string)

File.readFile(filepath)
^^^^^^^^^^^^^^^^^^^^^^^

Read and return the files contents for further use.

* ``filepath`` (string)

**Return** (string)

File.stat(filepath)
^^^^^^^^^^^^^^^^^^^

Get file information like creation and modification times as well as size.

* ``filepath`` (string)

**Return** (array) with keys mode, uid, gid, size, atime, mtime, ctime, (blksize, blocks, dev, ino, nlink, rdev)






.. _`Eel Helpers Reference: Json`:

Json
----

JSON helpers for Eel contexts

Implemented in: ``Neos\Eel\Helper\JsonHelper``

Json.parse(json, associativeArrays)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

JSON decode the given string

* ``json`` (string)
* ``associativeArrays`` (boolean, *optional*)

**Return** (mixed)

Json.stringify(value, options)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

JSON encode the given value

Usage example for options:

Json.stringify(value, ['JSON_UNESCAPED_UNICODE', 'JSON_FORCE_OBJECT'])

* ``value`` (mixed)
* ``options`` (array, *optional*) Array of option constant names as strings

**Return** (string)






.. _`Eel Helpers Reference: Math`:

Math
----

Math helpers for Eel contexts

The implementation sticks to the JavaScript specificiation including EcmaScript 6 proposals.

See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Math for a documentation and
specification of the JavaScript implementation.

Implemented in: ``Neos\Eel\Helper\MathHelper``

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

**Return** (boolean) true if the value is a finite (not NAN) number

Math.isInfinite(x)
^^^^^^^^^^^^^^^^^^

Test if the given value is an infinite number (INF or -INF)

This function has no direct equivalent in JavaScript.

* ``x`` (mixed) A value

**Return** (boolean) true if the value is INF or -INF

Math.isNaN(x)
^^^^^^^^^^^^^

Test if the given value is not a number (either not numeric or NAN)

This is equivalent to the global isNaN() function in JavaScript.

* ``x`` (mixed) A value

**Return** (boolean) true if the value is not a number

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

Implemented in: ``Neos\Neos\Fusion\Helper\ArrayHelper``

Neos.Array.filter(set, filterProperty)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to true.

* ``set`` (array|Collection)
* ``filterProperty`` (string)

**Return** (array)

Neos.Array.filterNegated(set, filterProperty)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to false.

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

Implemented in: ``Neos\Neos\Fusion\Helper\CachingHelper``

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

Neos.Caching.nodeTagForIdentifier(identifier, contextNode)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Generate a `@cache` entry tag for a single node identifier. If a NodeInterface $contextNode is given the
entry tag will respect the workspace hash.

* ``identifier`` (string)
* ``contextNode`` (NodeInterface|null, *optional*)

**Return** (string)

Neos.Caching.nodeTypeTag(nodeType, contextNode)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Generate an `@cache` entry tag for a node type
A cache entry with this tag will be flushed whenever a node
(for any variant) that is of the given node type(s)
(including inheritance) is updated.

* ``nodeType`` (string|NodeType|string[]|NodeType[])
* ``contextNode`` (NodeInterface|null, *optional*)

**Return** (string|string[])

Neos.Caching.renderWorkspaceTagForContextNode(workspaceName)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (string)

**Return** (string)






.. _`Eel Helpers Reference: Neos.Link`:

Neos.Link
---------

Eel helper for the linking service

Implemented in: ``Neos\Neos\Fusion\Helper\LinkHelper``

Neos.Link.convertUriToObject(uri, contextNode)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|UriInterface)
* ``contextNode`` (NodeInterface, *optional*)

**Return** (NodeInterface|AssetInterface|NULL)

Neos.Link.getScheme(uri)
^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|UriInterface)

**Return** (string)

Neos.Link.hasSupportedScheme(uri)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|UriInterface)

**Return** (boolean)

Neos.Link.resolveAssetUri(uri)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|UriInterface)

**Return** (string)

Neos.Link.resolveNodeUri(uri, contextNode, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``uri`` (string|UriInterface)
* ``contextNode`` (NodeInterface)
* ``controllerContext`` (ControllerContext)

**Return** (string)






.. _`Eel Helpers Reference: Neos.Node`:

Neos.Node
---------

Eel helper for ContentRepository Nodes

Implemented in: ``Neos\Neos\Fusion\Helper\NodeHelper``

Neos.Node.isOfType(node, nodeType)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If this node type or any of the direct or indirect super types
has the given name.

* ``node`` (NodeInterface)
* ``nodeType`` (string)

**Return** (bool)

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

Implemented in: ``Neos\Neos\Fusion\Helper\RenderingHelper``

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






.. _`Eel Helpers Reference: Neos.Seo.Image`:

Neos.Seo.Image
--------------



Implemented in: ``Neos\Seo\Fusion\Helper\ImageHelper``

Neos.Seo.Image.createThumbnail(asset, preset, width, maximumWidth, height, maximumHeight, allowCropping, allowUpScaling, async, quality, format)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``asset`` (AssetInterface)
* ``preset`` (string, *optional*) Name of the preset that should be used as basis for the configuration
* ``width`` (integer, *optional*) Desired width of the image
* ``maximumWidth`` (integer, *optional*) Desired maximum width of the image
* ``height`` (integer, *optional*) Desired height of the image
* ``maximumHeight`` (integer, *optional*) Desired maximum height of the image
* ``allowCropping`` (boolean, *optional*) Whether the image should be cropped if the given sizes would hurt the aspect ratio
* ``allowUpScaling`` (boolean, *optional*) Whether the resulting image size might exceed the size of the original image
* ``async`` (boolean, *optional*) Whether the thumbnail can be generated asynchronously
* ``quality`` (integer, *optional*) Quality of the processed image
* ``format`` (string, *optional*) Format for the image, only jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported.

**Return** (null|ImageInterface)






.. _`Eel Helpers Reference: Neos.Ui.PositionalArraySorter`:

Neos.Ui.PositionalArraySorter
-----------------------------



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\PositionalArraySorterHelper``

Neos.Ui.PositionalArraySorter.sort(array, positionPath)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``array`` (array)
* ``positionPath`` (string, *optional*)

**Return** (array)






.. _`Eel Helpers Reference: Neos.Ui.StaticResources`:

Neos.Ui.StaticResources
-----------------------



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\StaticResourcesHelper``

Neos.Ui.StaticResources.compiledResourcePackage()
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^






.. _`Eel Helpers Reference: Neos.Ui.Workspace`:

Neos.Ui.Workspace
-----------------



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\WorkspaceHelper``

Neos.Ui.Workspace.getAllowedTargetWorkspaces()
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Neos.Ui.Workspace.getPersonalWorkspace()
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Neos.Ui.Workspace.getPublishableNodeInfo(workspace)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspace`` (Workspace)

**Return** (array)






.. _`Eel Helpers Reference: NodeInfo`:

NodeInfo
--------



Implemented in: ``Neos\Neos\Ui\Fusion\Helper\NodeInfoHelper``

NodeInfo.createRedirectToNode(controllerContext, node)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Creates a URL that will redirect to the given $node in live or base workspace, or returns an empty string if that doesn't exist or is inaccessible

* ``controllerContext`` (ControllerContext)
* ``node`` (NodeInterface|null, *optional*)

**Return** (string)

NodeInfo.defaultNodesForBackend(site, documentNode, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``site`` (NodeInterface)
* ``documentNode`` (NodeInterface)
* ``controllerContext`` (ControllerContext)

**Return** (array)

NodeInfo.renderDocumentNodeAndChildContent(documentNode, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``documentNode`` (NodeInterface)
* ``controllerContext`` (ControllerContext)

**Return** (array)

NodeInfo.renderNodeWithMinimalPropertiesAndChildrenInformation(node, controllerContext, nodeTypeFilterOverride)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``node`` (NodeInterface)
* ``controllerContext`` (ControllerContext|null, *optional*)
* ``nodeTypeFilterOverride`` (string, *optional*)

**Return** (array|null)

NodeInfo.renderNodeWithPropertiesAndChildrenInformation(node, controllerContext, nodeTypeFilterOverride)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``node`` (NodeInterface)
* ``controllerContext`` (ControllerContext|null, *optional*)
* ``nodeTypeFilterOverride`` (string, *optional*)

**Return** (array|null)

NodeInfo.renderNodes(nodes, controllerContext, omitMostPropertiesForTreeState)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``nodes`` (array)
* ``controllerContext`` (ControllerContext)
* ``omitMostPropertiesForTreeState`` (bool, *optional*)

**Return** (array)

NodeInfo.renderNodesWithParents(nodes, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``nodes`` (array)
* ``controllerContext`` (ControllerContext)

**Return** (array)

NodeInfo.uri(node, controllerContext)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``node`` (NodeInterface)
* ``controllerContext`` (ControllerContext)

**Return** (string)






.. _`Eel Helpers Reference: Security`:

Security
--------

Helper for security related information

Implemented in: ``Neos\Eel\Helper\SecurityHelper``

Security.csrfToken()
^^^^^^^^^^^^^^^^^^^^

Returns CSRF token which is required for "unsafe" requests (e.g. POST, PUT, DELETE, ...)

**Return** (string)

Security.getAccount()
^^^^^^^^^^^^^^^^^^^^^

Get the account of the first authenticated token.

**Return** (Account|NULL)

Security.hasAccess(privilegeTarget, parameters)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Returns true, if access to the given privilege-target is granted

* ``privilegeTarget`` (string) The identifier of the privilege target to decide on
* ``parameters`` (array, *optional*) Optional array of privilege parameters (simple key => value array)

**Return** (boolean) true if access is granted, false otherwise

Security.hasRole(roleIdentifier)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Returns true, if at least one of the currently authenticated accounts holds
a role with the given identifier, also recursively.

* ``roleIdentifier`` (string) The string representation of the role to search for

**Return** (boolean) true, if a role with the given string representation was found

Security.isAuthenticated()
^^^^^^^^^^^^^^^^^^^^^^^^^^

Returns true, if any account is currently authenticated

**Return** (boolean) true if any account is authenticated






.. _`Eel Helpers Reference: StaticResource`:

StaticResource
--------------



Implemented in: ``Neos\Flow\ResourceManagement\EelHelper\StaticResourceHelper``

StaticResource.content(packageKey, pathAndFilename, localize)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the content of a package resource

* ``packageKey`` (string) Package key where the resource is from.
* ``pathAndFilename`` (string) The path and filename of the resource. Starting with "Public/..." or "Private/...
* ``localize`` (bool, *optional*) If enabled localizing of the resource is attempted by adding locales from the current locale-chain between filename and extension.

**Return** (string)

StaticResource.uri(packageKey, pathAndFilename, localize)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the public uri of a package resource

* ``packageKey`` (string) Package key where the resource is from.
* ``pathAndFilename`` (string) The path and filename of the resource. Has to start with "Public/..." as private resources do not have a uri.
* ``localize`` (bool, *optional*) If enabled localizing of the resource is attempted by adding locales from the current locale-chain between filename and extension.

**Return** (string)






.. _`Eel Helpers Reference: String`:

String
------

String helpers for Eel contexts

Implemented in: ``Neos\Eel\Helper\StringHelper``

String.base64decode(string, strict)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Implementation of the PHP base64_decode function

* ``string`` (string) The encoded data.
* ``strict`` (bool, *optional*) If TRUE this function will return FALSE if the input contains character from outside the base64 alphabet.

**Return** (string|bool) The decoded data or FALSE on failure. The returned data may be binary.

String.base64encode(string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Implementation of the PHP base64_encode function

* ``string`` (string) The data to encode.

**Return** (string) The encoded data

String.charAt(string, index)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the character at a specific position

Example::

    String.charAt("abcdefg", 5) == "f"

* ``string`` (string) The input string
* ``index`` (integer) The index to get

**Return** (string) The character at the given index

String.chr(value)
^^^^^^^^^^^^^^^^^

Generate a single-byte string from a number

Example::

    String.chr(65) == "A"

This is a wrapper for the chr() PHP function.

* ``value`` (int) An integer between 0 and 255

**Return** (string) A single-character string containing the specified byte

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

**Return** (boolean) true if the string ends with the given search

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

String.format(format, args)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Implementation of the PHP vsprintf function

* ``format`` (string) A formatting string containing directives
* ``args`` (array) An array of values to be inserted according to the formatting string $format

**Return** (string) A string produced according to the formatting string $format

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

Example::

    String.md5("joh316") == "bacb98acf97e0b6112b1d1b650b84971"

* ``string`` (string) The string to hash

**Return** (string) The MD5 hash of ``string``

String.nl2br(string)
^^^^^^^^^^^^^^^^^^^^

Insert HTML line breaks before all newlines in a string

Example::

    String.nl2br(someStingWithLinebreaks) == 'line1<br />line2'

This is a wrapper for the nl2br() PHP function.

* ``string`` (string) The input string

**Return** (string) The string with new lines replaced

String.ord(string)
^^^^^^^^^^^^^^^^^^

Convert the first byte of a string to a value between 0 and 255

Example::

    String.ord('A') == 65

This is a wrapper for the ord() PHP function.

* ``string`` (string) A character

**Return** (int) An integer between 0 and 255

String.pregMatch(string, pattern)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Match a string with a regular expression (PREG style)

Example::

    String.pregMatch("For more information, see Chapter 3.4.5.1", "/(chapter \d+(\.\d)*)/i")
      == ['Chapter 3.4.5.1', 'Chapter 3.4.5.1', '.1']

* ``string`` (string) The input string
* ``pattern`` (string) A PREG pattern

**Return** (array) The matches as array or NULL if not matched

String.pregMatchAll(string, pattern)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Perform a global regular expression match (PREG style)

Example::

    String.pregMatchAll("<hr id="icon-one" /><hr id="icon-two" />", '/id="icon-(.+?)"/')
      == [['id="icon-one"', 'id="icon-two"'],['one','two']]

* ``string`` (string) The input string
* ``pattern`` (string) A PREG pattern

**Return** (array) The matches as array or NULL if not matched

String.pregReplace(string, pattern, replace, limit)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Replace occurrences of a search string inside the string using regular expression matching (PREG style)

Examples::

    String.pregReplace("Some.String with sp:cial characters", "/[[:^alnum:]]/", "-") == "Some-String-with-sp-cial-characters"
    String.pregReplace("Some.String with sp:cial characters", "/[[:^alnum:]]/", "-", 1) == "Some-String with sp:cial characters"
    String.pregReplace("2016-08-31", "/([0-9]+)-([0-9]+)-([0-9]+)/", "$3.$2.$1") == "31.08.2016"

* ``string`` (string) The input string
* ``pattern`` (string) A PREG pattern
* ``replace`` (string) A replacement string, can contain references to capture groups with "\\n" or "$n
* ``limit`` (integer, *optional*) The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).

**Return** (string) The string with all occurrences replaced

String.pregSplit(string, pattern, limit)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Split a string by a separator using regular expression matching (PREG style)

Examples::

    String.pregSplit("foo bar   baz", "/\s+/") == ['foo', 'bar', 'baz']
    String.pregSplit("first second third", "/\s+/", 2) == ['first', 'second third']

* ``string`` (string) The input string
* ``pattern`` (string) A PREG pattern
* ``limit`` (integer, *optional*) The maximum amount of items to return, in contrast to split() this will return all remaining characters in the last item (see example)

**Return** (array) An array of the splitted parts, excluding the matched pattern

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

Example::

    String.replace("canal", "ana", "oo") == "cool"

Note: this method does not perform regular expression matching, @see pregReplace().

* ``string`` (string) The input string
* ``search`` (string) A search string
* ``replace`` (string) A replacement string

**Return** (string) The string with all occurrences replaced

String.sha1(string)
^^^^^^^^^^^^^^^^^^^

Calculate the SHA1 checksum of the given string

Example::

    String.sha1("joh316") == "063b3d108bed9f88fa618c6046de0dccadcf3158"

* ``string`` (string) The string to hash

**Return** (string) The SHA1 hash of ``string``

String.split(string, separator, limit)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Split a string by a separator

Example::

    String.split("My hovercraft is full of eels", " ") == ['My', 'hovercraft', 'is', 'full', 'of', 'eels']
    String.split("Foo", "", 2) == ['F', 'o']

Node: This implementation follows JavaScript semantics without support of regular expressions.

* ``string`` (string) The string to split
* ``separator`` (string, *optional*) The separator where the string should be splitted
* ``limit`` (integer, *optional*) The maximum amount of items to split (exceeding items will be discarded)

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

String.stripTags(string, allowableTags)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Strip all HTML tags from the given string

Example::

    String.stripTags('<a href="#">Some link</a>') == 'Some link'

This is a wrapper for the strip_tags() PHP function.

* ``string`` (string) The string to strip
* ``allowableTags`` (string, *optional*) Specify tags which should not be stripped

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

A value is ``true``, if it is either the string ``"true"`` or ``"true"`` or the number ``1``.

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

String.wordCount(unicodeString)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Return the count of words for a given string. Remove marks & digits and
flatten all kind of whitespaces (tabs, new lines and multiple spaces)
For example this helper can be utilized to calculate the reading time of an article.

* ``unicodeString`` (string) The input string

**Return** (integer) Number of words






.. _`Eel Helpers Reference: Translation`:

Translation
-----------

Translation helpers for Eel contexts

Implemented in: ``Neos\Flow\I18n\EelHelper\TranslationHelper``

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
* ``arguments`` (array, *optional*) Array of numerically indexed or named values to be inserted into placeholders. Have a look at the internationalization documentation in the definitive guide for details.
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

Implemented in: ``Neos\Eel\Helper\TypeHelper``

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





