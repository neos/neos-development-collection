.. _`TYPO3 Fluid ViewHelper Reference`:

TYPO3 Fluid ViewHelper Reference
################################

This reference was automatically generated from code on 2024-05-15


.. _`TYPO3 Fluid ViewHelper Reference: f:alias`:

f:alias
-------

Declares new variables which are aliases of other variables.
Takes a "map"-Parameter which is an associative array which defines the shorthand mapping.

The variables are only declared inside the ``<f:alias>...</f:alias>`` tag. After the
closing tag, all declared variables are removed again.

Using this ViewHelper can be a sign of weak architecture. If you end up
using it extensively you might want to fine-tune your "view model" (the
data you assign to the view).

Examples
========

Single alias
------------

::

    <f:alias map="{x: 'foo'}">{x}</f:alias>

Output::

    foo

Multiple mappings
-----------------

::

    <f:alias map="{x: foo.bar.baz, y: foo.bar.baz.name}">
        {x.name} or {y}
    </f:alias>

Output::

    [name] or [name]

Depending on ``{foo.bar.baz}``.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\AliasViewHelper




Arguments
*********

* ``map`` (array): Array that specifies which variables should be mapped to which alias




.. _`TYPO3 Fluid ViewHelper Reference: f:cache.disable`:

f:cache.disable
---------------

ViewHelper to disable template compiling

Inserting this ViewHelper at any point in the template,
including inside conditions which do not get rendered,
will forcibly disable the caching/compiling of the full
template file to a PHP class.

Use this if for whatever reason your platform is unable
to create or load PHP classes (for example on read-only
file systems or when using an incompatible default cache
backend).

Passes through anything you place inside the ViewHelper,
so can safely be used as container tag, as self-closing
or with inline syntax - all with the same result.

Examples
========

Self-closing
------------

::

    <f:cache.disable />

Inline mode
-----------

::

    {f:cache.disable()}


Container tag
-------------

::

    <f:cache.disable>
       Some output or Fluid code
    </f:cache.disable>

Additional output is also not compilable because of the ViewHelper

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Cache\\DisableViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:cache.static`:

f:cache.static
--------------

ViewHelper to force compiling to a static string

Used around chunks of template code where you want the
output of said template code to be compiled to a static
string (rather than a collection of compiled nodes, as
is the usual behavior).

The effect is that none of the child ViewHelpers or nodes
used inside this tag will be evaluated when rendering the
template once it is compiled. It will essentially replace
all logic inside the tag with a plain string output.

Works by turning the ``compile`` method into a method that
renders the child nodes and returns the resulting content
directly as a string variable.

You can use this with great effect to further optimise the
performance of your templates: in use cases where chunks of
template code depend on static variables (like thoese in
``{settings}`` for example) and those variables never change,
and the template uses no other dynamic variables, forcing
the template to compile that chunk to a static string can
save a lot of operations when rendering the compiled template.

NB: NOT TO BE USED FOR CACHING ANYTHING OTHER THAN STRING-
COMPATIBLE OUTPUT!

USE WITH CARE! WILL PRESERVE EVERYTHING RENDERED, INCLUDING
POTENTIALLY SENSITIVE DATA CONTAINED IN OUTPUT!

Examples
========

Usage and effect
----------------

::

    <f:if condition="{var}">Is always evaluated also when compiled</f:if>
    <f:cache.static>
        <f:if condition="{othervar}">
            Will only be evaluated once and this output will be
            cached as a static string with no logic attached.
            The compiled template will not contain neither the
            condition ViewHelperNodes or the variable accessor
            that are used inside this node.
        </f:if>
    </f:cache.static>

This is also evaluated when compiled (static node is closed)::

    <f:if condition="{var}">Also evaluated; is outside static node</f:if>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Cache\\StaticViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:cache.warmup`:

f:cache.warmup
--------------

ViewHelper to insert variables which only apply during
cache warmup and only apply if no other variables are
specified for the warmup process.

If a chunk of template code is impossible to compile
without additional variables, for example when rendering
sections or partials using dynamic names, you can use this
ViewHelper around that chunk and specify a set of variables
which will be assigned only while compiling the template
and only when this is done as part of cache warmup. The
template chunk can then be compiled using those default
variables.

This does not imply that only those variable values will
be used by the compiled template. It only means that
DEFAULT values of vital variables will be present during
compiling.

If you find yourself completely unable to properly warm up
a specific template file even with use of this ViewHelper,
then you can consider using
``f:cache.disable`` ViewHelper
to prevent the template compiler from even attempting to
compile it.

USE WITH CARE! SOME EDGE CASES OF FOR EXAMPLE VIEWHELPERS
WHICH REQUIRE SPECIAL VARIABLE TYPES MAY NOT BE SUPPORTED
HERE DUE TO THE RUDIMENTARY NATURE OF VARIABLES YOU DEFINE.

Examples
========

Usage and effect
----------------

::

    <f:cache.warmup variables="{foo: bar}">
       Template code depending on {foo} variable which is not
       assigned when warming up Fluid's caches. {foo} is only
       assigned if the variable does not already exist and the
       assignment only happens if Fluid is in warmup mode.
    </f:cache.warmup>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Cache\\WarmupViewHelper




Arguments
*********

* ``variables`` (array, *optional*): Array of variables to assign ONLY when compiling. See main class documentation.




.. _`TYPO3 Fluid ViewHelper Reference: f:case`:

f:case
------

Case ViewHelper that is only usable within the ``f:switch`` ViewHelper.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\CaseViewHelper




Arguments
*********

* ``value`` (mixed): Value to match in this case




.. _`TYPO3 Fluid ViewHelper Reference: f:comment`:

f:comment
---------

This ViewHelper prevents rendering of any content inside the tag.

Contents of the comment will still be **parsed** thus throwing an
Exception if it contains syntax errors. You can put child nodes in
CDATA tags to avoid this.

Using this ViewHelper won't have a notable effect on performance,
especially once the template is parsed.  However, it can lead to reduced
readability. You can use layouts and partials to split a large template
into smaller parts. Using self-descriptive names for the partials can
make comments redundant.

Examples
========

Commenting out fluid code
-------------------------

::

    Before
    <f:comment>
        This is completely hidden.
        <f:debug>This does not get rendered</f:debug>
    </f:comment>
    After

Output::

    Before
    After

Prevent parsing
---------------

::

    <f:comment><![CDATA[
       <f:some.invalid.syntax />
    ]]></f:comment>

Output:

Will be nothing.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\CommentViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:count`:

f:count
-------

This ViewHelper counts elements of the specified array or countable object.

Examples
========

Count array elements
--------------------

::

    <f:count subject="{0:1, 1:2, 2:3, 3:4}" />

Output::

    4

inline notation
---------------

::

    {objects -> f:count()}

Output::

    10 (depending on the number of items in ``{objects}``)

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\CountViewHelper




Arguments
*********

* ``subject`` (array, *optional*): Countable subject, array or \Countable




.. _`TYPO3 Fluid ViewHelper Reference: f:cycle`:

f:cycle
-------

This ViewHelper cycles through the specified values.
This can be often used to specify CSS classes for example.

To achieve the "zebra class" effect in a loop you can also use the
"iteration" argument of the **for** ViewHelper.

Examples
========

These examples could also be achieved using the "iteration" argument
of the ForViewHelper.

Simple
------

::

    <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">
        <f:cycle values="{0: 'foo', 1: 'bar', 2: 'baz'}" as="cycle">
            {cycle}
        </f:cycle>
    </f:for>

Output::

    foobarbazfoo

Alternating CSS class
---------------------

::

    <ul>
        <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">
            <f:cycle values="{0: 'odd', 1: 'even'}" as="zebraClass">
                <li class="{zebraClass}">{foo}</li>
            </f:cycle>
        </f:for>
    </ul>

Output::

    <ul>
        <li class="odd">1</li>
        <li class="even">2</li>
        <li class="odd">3</li>
        <li class="even">4</li>
    </ul>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\CycleViewHelper




Arguments
*********

* ``values`` (array, *optional*): The array or object implementing \ArrayAccess (for example \SplObjectStorage) to iterated over

* ``as`` (string): The name of the iteration variable




.. _`TYPO3 Fluid ViewHelper Reference: f:debug`:

f:debug
-------

This ViewHelper is only meant to be used during development.

Examples
========

Inline notation and custom title
--------------------------------

::

    {object -> f:debug(title: 'Custom title')}

Output::

    all properties of {object} nicely highlighted (with custom title)

Only output the type
--------------------

::

    {object -> f:debug(typeOnly: true)}

Output::

    the type or class name of {object}

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\DebugViewHelper




Arguments
*********

* ``typeOnly`` (boolean, *optional*): If TRUE, debugs only the type of variables

* ``levels`` (integer, *optional*): Levels to render when rendering nested objects/arrays

* ``html`` (boolean, *optional*): Render HTML. If FALSE, output is indented plaintext




.. _`TYPO3 Fluid ViewHelper Reference: f:defaultCase`:

f:defaultCase
-------------

A ViewHelper which specifies the "default" case when used within the ``f:switch`` ViewHelper.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\DefaultCaseViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:else`:

f:else
------

Else-Branch of a condition. Only has an effect inside of ``f:if``.
See the ``f:if`` ViewHelper for documentation.

Examples
========

Output content if condition is not met
--------------------------------------

::

    <f:if condition="{someCondition}">
        <f:else>
            condition was not true
        </f:else>
    </f:if>

Output::

    Everything inside the "else" tag is displayed if the condition evaluates to FALSE.
    Otherwise nothing is outputted in this example.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\ElseViewHelper




Arguments
*********

* ``if`` (boolean, *optional*): Condition expression conforming to Fluid boolean rules




.. _`TYPO3 Fluid ViewHelper Reference: f:first`:

f:first
-------

The FirstViewHelper returns the first item of an array.

Example
========

::

   <f:first value="{0: 'first', 1: 'second'}" />

.. code-block:: text

   first

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\FirstViewHelper




Arguments
*********

* ``value`` (array, *optional*)




.. _`TYPO3 Fluid ViewHelper Reference: f:for`:

f:for
-----

Loop ViewHelper which can be used to iterate over arrays.
Implements what a basic PHP ``foreach()`` does.

Examples
========

Simple Loop
-----------

::

    <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">{foo}</f:for>

Output::

    1234

Output array key
----------------

::

    <ul>
        <f:for each="{fruit1: 'apple', fruit2: 'pear', fruit3: 'banana', fruit4: 'cherry'}"
            as="fruit" key="label"
        >
            <li>{label}: {fruit}</li>
        </f:for>
    </ul>

Output::

    <ul>
        <li>fruit1: apple</li>
        <li>fruit2: pear</li>
        <li>fruit3: banana</li>
        <li>fruit4: cherry</li>
    </ul>

Iteration information
---------------------

::

    <ul>
        <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo" iteration="fooIterator">
            <li>Index: {fooIterator.index} Cycle: {fooIterator.cycle} Total: {fooIterator.total}{f:if(condition: fooIterator.isEven, then: ' Even')}{f:if(condition: fooIterator.isOdd, then: ' Odd')}{f:if(condition: fooIterator.isFirst, then: ' First')}{f:if(condition: fooIterator.isLast, then: ' Last')}</li>
        </f:for>
    </ul>

Output::

    <ul>
        <li>Index: 0 Cycle: 1 Total: 4 Odd First</li>
        <li>Index: 1 Cycle: 2 Total: 4 Even</li>
        <li>Index: 2 Cycle: 3 Total: 4 Odd</li>
        <li>Index: 3 Cycle: 4 Total: 4 Even Last</li>
    </ul>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\ForViewHelper




Arguments
*********

* ``each`` (array): The array or \SplObjectStorage to iterated over

* ``as`` (string): The name of the iteration variable

* ``key`` (string, *optional*): Variable to assign array key to

* ``reverse`` (boolean, *optional*): If TRUE, iterates in reverse

* ``iteration`` (string, *optional*): The name of the variable to store iteration information (index, cycle, total, isFirst, isLast, isEven, isOdd)




.. _`TYPO3 Fluid ViewHelper Reference: f:format.case`:

f:format.case
-------------

Modifies the case of an input string to upper- or lowercase or capitalization.
The default transformation will be uppercase as in `mb_convert_case`_.

Possible modes are:

``lower``
  Transforms the input string to its lowercase representation

``upper``
  Transforms the input string to its uppercase representation

``capital``
  Transforms the input string to its first letter upper-cased, i.e. capitalization

``uncapital``
  Transforms the input string to its first letter lower-cased, i.e. uncapitalization

``capitalWords``
  Not supported yet: Transforms the input string to each containing word being capitalized

Note that the behavior will be the same as in the appropriate PHP function `mb_convert_case`_;
especially regarding locale and multibyte behavior.

.. _mb_convert_case: https://www.php.net/manual/function.mb-convert-case.php

Examples
========

Default
-------

::

   <f:format.case>Some Text with miXed case</f:format.case>

Output::

   SOME TEXT WITH MIXED CASE

Example with given mode
-----------------------

::

   <f:format.case mode="capital">someString</f:format.case>

Output::

   SomeString

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\CaseViewHelper




Arguments
*********

* ``value`` (string, *optional*): The input value. If not given, the evaluated child nodes will be used.

* ``mode`` (string, *optional*): The case to apply, must be one of this' CASE_* constants. Defaults to uppercase application.




.. _`TYPO3 Fluid ViewHelper Reference: f:format.cdata`:

f:format.cdata
--------------

Outputs an argument/value without any escaping and wraps it with CDATA tags.

PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
as the output is NOT SANITIZED!

Examples
========

Child nodes
-----------

::

    <f:format.cdata>{string}</f:format.cdata>

Output::

    <![CDATA[(Content of {string} without any conversion/escaping)]]>

Value attribute
---------------

::

    <f:format.cdata value="{string}" />

Output::

    <![CDATA[(Content of {string} without any conversion/escaping)]]>

Inline notation
---------------

::

    {string -> f:format.cdata()}

Output::

    <![CDATA[(Content of {string} without any conversion/escaping)]]>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\CdataViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The value to output




.. _`TYPO3 Fluid ViewHelper Reference: f:format.htmlspecialchars`:

f:format.htmlspecialchars
-------------------------

Applies PHP ``htmlspecialchars()`` escaping to a value.

See http://www.php.net/manual/function.htmlspecialchars.php

Examples
========

Default notation
----------------

::

    <f:format.htmlspecialchars>{text}</f:format.htmlspecialchars>

Output::

    Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).

Inline notation
---------------

::

    {text -> f:format.htmlspecialchars(encoding: 'ISO-8859-1')}

Output::

    Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\HtmlspecialcharsViewHelper




Arguments
*********

* ``value`` (string, *optional*): Value to format

* ``keepQuotes`` (boolean, *optional*): If TRUE quotes will not be replaced (ENT_NOQUOTES)

* ``encoding`` (string, *optional*): Encoding

* ``doubleEncode`` (boolean, *optional*): If FALSE html entities will not be encoded




.. _`TYPO3 Fluid ViewHelper Reference: f:format.json`:

f:format.json
-------------

Wrapper for PHPs :php:`json_encode` function.
See https://www.php.net/manual/function.json-encode.php.

Examples
========

Encoding a view variable
------------------------

::

   {someArray -> f:format.json()}

``["array","values"]``
Depending on the value of ``{someArray}``.

Associative array
-----------------

::

   {f:format.json(value: {foo: 'bar', bar: 'baz'})}

``{"foo":"bar","bar":"baz"}``

Non associative array with forced object
----------------------------------------

::

   {f:format.json(value: {0: 'bar', 1: 'baz'}, forceObject: true)}

``{"0":"bar","1":"baz"}``

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\JsonViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The incoming data to convert, or null if VH children should be used

* ``forceObject`` (bool, *optional*): Outputs an JSON object rather than an array




.. _`TYPO3 Fluid ViewHelper Reference: f:format.nl2br`:

f:format.nl2br
--------------

Wrapper for PHPs :php:`nl2br` function.
See https://www.php.net/manual/function.nl2br.php.

Examples
========

Default
-------

::

   <f:format.nl2br>{text_with_linebreaks}</f:format.nl2br>

Text with line breaks replaced by ``<br />``

Inline notation
---------------

::

   {text_with_linebreaks -> f:format.nl2br()}

Text with line breaks replaced by ``<br />``

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format




.. _`TYPO3 Fluid ViewHelper Reference: f:format.number`:

f:format.number
---------------

Formats a number with custom precision, decimal point and grouped thousands.
See https://www.php.net/manual/function.number-format.php.

Examples
========

Defaults
--------

::

   <f:format.number>423423.234</f:format.number>

``423,423.20``

With all parameters
-------------------

::

   <f:format.number decimals="1" decimalSeparator="," thousandsSeparator=".">
       423423.234
   </f:format.number>

``423.423,2``

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\NumberViewHelper




Arguments
*********

* ``decimals`` (int, *optional*): The number of digits after the decimal point

* ``decimalSeparator`` (string, *optional*): The decimal point character

* ``thousandsSeparator`` (string, *optional*): The character for grouping the thousand digits




.. _`TYPO3 Fluid ViewHelper Reference: f:format.printf`:

f:format.printf
---------------

A ViewHelper for formatting values with printf. Either supply an array for
the arguments or a single value.

See http://www.php.net/manual/en/function.sprintf.php

Examples
========

Scientific notation
-------------------

::

    <f:format.printf arguments="{number: 362525200}">%.3e</f:format.printf>

Output::

    3.625e+8

Argument swapping
-----------------

::

    <f:format.printf arguments="{0: 3, 1: 'Kasper'}">%2$s is great, TYPO%1$d too. Yes, TYPO%1$d is great and so is %2$s!</f:format.printf>

Output::

    Kasper is great, TYPO3 too. Yes, TYPO3 is great and so is Kasper!

Single argument
---------------

::

    <f:format.printf arguments="{1: 'TYPO3'}">We love %s</f:format.printf>


Output::

    We love TYPO3

Inline notation
---------------

::

    {someText -> f:format.printf(arguments: {1: 'TYPO3'})}


Output::

    We love TYPO3

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\PrintfViewHelper




Arguments
*********

* ``value`` (string, *optional*): String to format

* ``arguments`` (array, *optional*): The arguments for vsprintf




.. _`TYPO3 Fluid ViewHelper Reference: f:format.raw`:

f:format.raw
------------

Outputs an argument/value without any escaping. Is normally used to output
an ObjectAccessor which should not be escaped, but output as-is.

PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
as the output is NOT SANITIZED!

Examples
========

Child nodes
-----------

::

    <f:format.raw>{string}</f:format.raw>

Output::

    (Content of ``{string}`` without any conversion/escaping)

Value attribute
---------------

::

    <f:format.raw value="{string}" />

Output::

    (Content of ``{string}`` without any conversion/escaping)

Inline notation
---------------

::

    {string -> f:format.raw()}

Output::

    (Content of ``{string}`` without any conversion/escaping)

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\RawViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): The value to output




.. _`TYPO3 Fluid ViewHelper Reference: f:format.stripTags`:

f:format.stripTags
------------------

Removes tags from the given string (applying PHPs :php:`strip_tags()` function)
See https://www.php.net/manual/function.strip-tags.php.

Examples
========

Default notation
----------------

::

   <f:format.stripTags>Some Text with <b>Tags</b> and an &Uuml;mlaut.</f:format.stripTags>

Some Text with Tags and an &Uuml;mlaut. :php:`strip_tags()` applied.

.. note::
   Encoded entities are not decoded.

Default notation with allowedTags
---------------------------------

::

   <f:format.stripTags allowedTags="<p><span><div><script>">
       <p>paragraph</p><span>span</span><div>divider</div><iframe>iframe</iframe><script>script</script>
   </f:format.stripTags>

Output::

   <p>paragraph</p><span>span</span><div>divider</div>iframe<script>script</script>

Inline notation
---------------

::

   {text -> f:format.stripTags()}

Text without tags :php:`strip_tags()` applied.

Inline notation with allowedTags
--------------------------------

::

   {text -> f:format.stripTags(allowedTags: "<p><span><div><script>")}

Text with p, span, div and script Tags inside, all other tags are removed.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\StripTagsViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format

* ``allowedTags`` (string, *optional*): Optional string of allowed tags as required by PHPs strip_tags() function




.. _`TYPO3 Fluid ViewHelper Reference: f:format.trim`:

f:format.trim
-------------

This ViewHelper strips whitespace (or other characters) from the beginning and end of a string.

Possible sides are:

``both`` (default)
  Strip whitespace (or other characters) from the beginning and end of a string

``left`` or ``start``
  Strip whitespace (or other characters) from the beginning of a string

``right`` or ``end``
  Strip whitespace (or other characters) from the end of a string


Examples
========

Defaults
--------
::

   #<f:format.trim>   String to be trimmed.   </f:format.trim>#

.. code-block:: text

   #String to be trimmed.#


Trim only one side
------------------

::

   #<f:format.trim side="right">   String to be trimmed.   </f:format.trim>#

.. code-block:: text

   #   String to be trimmed.#


Trim special characters
-----------------------

::

   #<f:format.trim characters=" St.">   String to be trimmed.   </f:format.trim>#

.. code-block:: text

   #ring to be trimmed#

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\TrimViewHelper




Arguments
*********

* ``value`` (string, *optional*): The string value to be trimmed. If not given, the evaluated child nodes will be used.

* ``characters`` (string, *optional*): Optionally, the stripped characters can also be specified using the characters parameter. Simply list all characters that you want to be stripped. With .. you can specify a range of characters.

* ``side`` (string, *optional*): The side to apply, must be one of this' CASE_* constants. Defaults to both application.




.. _`TYPO3 Fluid ViewHelper Reference: f:format.urlencode`:

f:format.urlencode
------------------

Encodes the given string according to http://www.faqs.org/rfcs/rfc3986.html
Applying PHPs :php:`rawurlencode()` function.
See https://www.php.net/manual/function.rawurlencode.php.

.. note::
   The output is not escaped. You may have to ensure proper escaping on your own.

Examples
========

Default notation
----------------

::

   <f:format.urlencode>foo @+%/</f:format.urlencode>

``foo%20%40%2B%25%2F`` :php:`rawurlencode()` applied.

Inline notation
---------------

::

   {text -> f:format.urlencode()}

Url encoded text :php:`rawurlencode()` applied.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\Format\\UrlencodeViewHelper




Arguments
*********

* ``value`` (string, *optional*): string to format




.. _`TYPO3 Fluid ViewHelper Reference: f:groupedFor`:

f:groupedFor
------------

Grouped loop ViewHelper.
Loops through the specified values.

The groupBy argument also supports property paths.

Using this ViewHelper can be a sign of weak architecture. If you end up
using it extensively you might want to fine-tune your "view model" (the
data you assign to the view).

Examples
========

Simple
------

::

    <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}"
        as="fruitsOfThisColor" groupBy="color"
    >
        <f:for each="{fruitsOfThisColor}" as="fruit">
            {fruit.name}
        </f:for>
    </f:groupedFor>

Output::

    apple cherry strawberry banana

Two dimensional list
--------------------

::

    <ul>
        <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color" groupKey="color">
            <li>
                {color} fruits:
                <ul>
                    <f:for each="{fruitsOfThisColor}" as="fruit" key="label">
                        <li>{label}: {fruit.name}</li>
                    </f:for>
                </ul>
            </li>
        </f:groupedFor>
    </ul>

Output::

    <ul>
        <li>green fruits
            <ul>
                <li>0: apple</li>
            </ul>
        </li>
        <li>red fruits
            <ul>
                <li>1: cherry</li>
            </ul>
            <ul>
                <li>3: strawberry</li>
            </ul>
        </li>
        <li>yellow fruits
            <ul>
                <li>2: banana</li>
            </ul>
        </li>
    </ul>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\GroupedForViewHelper




Arguments
*********

* ``each`` (array): The array or \SplObjectStorage to iterated over

* ``as`` (string): The name of the iteration variable

* ``groupBy`` (string): Group by this property

* ``groupKey`` (string, *optional*): The name of the variable to store the current group




.. _`TYPO3 Fluid ViewHelper Reference: f:if`:

f:if
----

This ViewHelper implements an if/else condition.

Fluid Boolean Rules / Conditions:
=================================

A condition is evaluated as a boolean value, so you can use any
boolean argument, like a variable.
Alternatively, you can use a full boolean expression.
The entered expression is evaluated as a PHP expression. You can
combine multiple expressions via :php:`&&` (logical AND) and
:php:`||` (logical OR).

An expression can also be prepended with the :php:`!` ("not") character,
which will negate that expression.

Have a look into the Fluid section of the "TYPO3 Explained" Documentation
for more details about complex conditions.

Boolean expressions have the following form:

`is true` variant: `{variable}`::

      <f:if condition="{foo}">
          Will be shown if foo is truthy.
      </f:if>

or `is false` variant: `!{variable}`::

      <f:if condition="!{foo}">
          Will be shown if foo is falsy.
      </f:if>

or comparisons with expressions::

      XX Comparator YY

Comparator is one of: :php:`==, !=, <, <=, >, >=` and :php:`%`
The :php:`%` operator (modulo) converts the result of the operation to
boolean.

`XX` and `YY` can be one of:

- Number
- String
- Object Accessor (`object.property`)
- Array
- a ViewHelper

::

      <f:if condition="{rank} > 100">
          Will be shown if rank is > 100
      </f:if>
      <f:if condition="{rank} % 2">
          Will be shown if rank % 2 != 0.
      </f:if>
      <f:if condition="{rank} == {k:bar()}">
          Checks if rank is equal to the result of the ViewHelper "k:bar"
      </f:if>
      <f:if condition="{object.property} == 'stringToCompare'">
          Will result in true if {object.property}'s represented value
          equals 'stringToCompare'.
      </f:if>

Examples
========

Basic usage
-----------

::

    <f:if condition="somecondition">
        This is being shown in case the condition matches
    </f:if>

Output::

    Everything inside the <f:if> tag is being displayed if the condition evaluates to TRUE.

If / then / else
----------------

::

    <f:if condition="somecondition">
        <f:then>
            This is being shown in case the condition matches.
        </f:then>
        <f:else>
            This is being displayed in case the condition evaluates to FALSE.
        </f:else>
    </f:if>

Output::

    Everything inside the "then" tag is displayed if the condition evaluates to TRUE.
    Otherwise, everything inside the "else" tag is displayed.

Inline notation
---------------

::

    {f:if(condition: someCondition, then: 'condition is met', else: 'condition is not met')}

Output::

    The value of the "then" attribute is displayed if the condition evaluates to TRUE.
    Otherwise, everything the value of the "else" attribute is displayed.

Combining multiple conditions
-----------------------------

::

    <f:if condition="{user.rank} > 100 && {user.type} == 'contributor'">
        <f:then>
            This is being shown in case both conditions match.
        </f:then>
        <f:else if="{user.rank} > 200 && ({user.type} == 'contributor' || {user.type} == 'developer')">
            This is being displayed in case the first block of the condition evaluates to TRUE and any condition in
            the second condition block evaluates to TRUE.
        </f:else>
        <f:else>
            This is being displayed when none of the above conditions evaluated to TRUE.
        </f:else>
    </f:if>

Output::

    Depending on which expression evaluated to TRUE, that value is displayed.
    If no expression matched, the contents inside the final "else" tag are displayed.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\IfViewHelper




Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``condition`` (boolean, *optional*): Condition expression conforming to Fluid boolean rules




.. _`TYPO3 Fluid ViewHelper Reference: f:inline`:

f:inline
--------

Inline Fluid rendering ViewHelper

Renders Fluid code stored in a variable, which you normally would
have to render before assigning it to the view. Instead you can
do the following (note, extremely simplified use case)::

     $view->assign('variable', 'value of my variable');
     $view->assign('code', 'My variable: {variable}');

And in the template::

     {code -> f:inline()}

Which outputs::

     My variable: value of my variable

You can use this to pass smaller and dynamic pieces of Fluid code
to templates, as an alternative to creating new partial templates.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\InlineViewHelper




Arguments
*********

* ``code`` (string, *optional*): Fluid code to be rendered as if it were part of the template rendering it. Can be passed as inline argument or tag content




.. _`TYPO3 Fluid ViewHelper Reference: f:join`:

f:join
------

The JoinViewHelper combines elements from an array into a single string.
You can specify both a general separator and a special one for the last
element, which serves as the delimiter between the elements.


Examples
========

Simple join
-----------
::

   <f:join value="{0: '1', 1: '2', 2: '3'}" />

.. code-block:: text

   123


Join with separator
-------------------

::

   <f:join value="{0: '1', 1: '2', 2: '3'}" separator=", " />

.. code-block:: text

   1, 2, 3


Join with separator, and special one for the last
-------------------------------------------------

::

   <f:join value="{0: '1', 1: '2', 2: '3'}" separator=", " separatorLast=" and " />

.. code-block:: text

   1, 2 and 3

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\JoinViewHelper




Arguments
*********

* ``value`` (array, *optional*): An array

* ``separator`` (string, *optional*): The separator

* ``separatorLast`` (string, *optional*): The separator for the last pair.




.. _`TYPO3 Fluid ViewHelper Reference: f:last`:

f:last
------

The LastViewHelper returns the last item of an array.

Example
========

::

   <f:last value="{0: 'first', 1: 'second'}" />

.. code-block:: text

   second

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\LastViewHelper




Arguments
*********

* ``value`` (array, *optional*)




.. _`TYPO3 Fluid ViewHelper Reference: f:layout`:

f:layout
--------

With this tag, you can select a layout to be used for the current template.

Examples
========

::

    <f:layout name="main" />

Output::

    (no output)

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\LayoutViewHelper




Arguments
*********

* ``name`` (string, *optional*): Name of layout to use. If none given, "Default" is used.




.. _`TYPO3 Fluid ViewHelper Reference: f:or`:

f:or
----

Or ViewHelper

If content is null use alternative text.

Usage of f:or
=============

::

    {f:variable(name:'fallback',value:'this is not the variable you\'re looking for')}
    {undefinedVariable -> f:or(alternative:fallback)}

Usage of ternary operator
=========================

In some cases (e.g. when you want to check for empty instead of null)
it might be more handy to use a ternary operator instead of f:or

::

    {emptyVariable ?: 'this is an alterative text'}

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\OrViewHelper




Arguments
*********

* ``content`` (mixed, *optional*): Content to check if null

* ``alternative`` (mixed, *optional*): Alternative if content is null

* ``arguments`` (array, *optional*): Arguments to be replaced in the resulting string, using sprintf




.. _`TYPO3 Fluid ViewHelper Reference: f:render`:

f:render
--------

A ViewHelper to render a section, a partial, a specified section in a partial
or a delegate ParsedTemplateInterface implementation.

Examples
========

Rendering partials
------------------

::

    <f:render partial="SomePartial" arguments="{foo: someVariable}" />

Output::

    the content of the partial "SomePartial". The content of the variable {someVariable} will be available in the partial as {foo}

Rendering sections
------------------

::

    <f:section name="someSection">This is a section. {foo}</f:section>
    <f:render section="someSection" arguments="{foo: someVariable}" />

Output::

    the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}

Rendering recursive sections
----------------------------

::

    <f:section name="mySection">
        <ul>
            <f:for each="{myMenu}" as="menuItem">
                <li>
                    {menuItem.text}
                    <f:if condition="{menuItem.subItems}">
                        <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
                    </f:if>
                </li>
            </f:for>
        </ul>
       </f:section>
       <f:render section="mySection" arguments="{myMenu: menu}" />

Output::

    <ul>
        <li>menu1
            <ul>
              <li>menu1a</li>
              <li>menu1b</li>
            </ul>
        </li>
    [...]
    (depending on the value of {menu})


Passing all variables to a partial
----------------------------------

::

    <f:render partial="somePartial" arguments="{_all}" />

Output::

    the content of the partial "somePartial".
    Using the reserved keyword "_all", all available variables will be passed along to the partial


Rendering via a delegate ParsedTemplateInterface implementation w/ custom arguments
-----------------------------------------------------------------------------------

::

    <f:render delegate="My\Special\ParsedTemplateImplementation" arguments="{_all}" />

This will output whichever output was generated by calling ``My\Special\ParsedTemplateImplementation->render()``
with cloned RenderingContextInterface $renderingContext as only argument and content of arguments
assigned in VariableProvider of cloned context. Supports all other input arguments including
recursive rendering, contentAs argument, default value etc.

Note that while ParsedTemplateInterface supports returning a Layout name, this Layout will not
be respected when rendering using this method. Only the ``render()`` method will be called!

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\RenderViewHelper




Arguments
*********

* ``section`` (string, *optional*): Section to render - combine with partial to render section in partial

* ``partial`` (string, *optional*): Partial to render, with or without section

* ``delegate`` (string, *optional*): Optional PHP class name of a permanent, included-in-app ParsedTemplateInterface implementation to override partial/section

* ``arguments`` (array, *optional*): Array of variables to be transferred. Use {_all} for all variables

* ``optional`` (boolean, *optional*): If TRUE, considers the *section* optional. Partial never is.

* ``default`` (mixed, *optional*): Value (usually string) to be displayed if the section or partial does not exist

* ``contentAs`` (string, *optional*): If used, renders the child content and adds it as a template variable with this name for use in the partial/section




.. _`TYPO3 Fluid ViewHelper Reference: f:replace`:

f:replace
---------

The ReplaceViewHelper replaces one or multiple strings with other
strings. This ViewHelper mimicks PHP's :php:`str_replace()` function.
However, it's also possible to provide replace pairs as associative array
via the "replace" argument.


Examples
========

Replace a single string
-----------------------
::

   <f:replace value="Hello World" search="World" replace="Fluid" />

.. code-block:: text

   Hello Fluid


Replace multiple strings
------------------------
::

   <f:replace value="Hello World" search="{0: 'World', 1: 'Hello'}" replace="{0: 'Fluid', 1: 'Hi'}" />

.. code-block:: text

   Hi Fluid


Replace multiple strings using associative array
------------------------------------------------
::

   <f:replace value="Hello World" replace="{'World': 'Fluid', 'Hello': 'Hi'}" />

.. code-block:: text

   Hi Fluid

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\ReplaceViewHelper




Arguments
*********

* ``value`` (string, *optional*)

* ``search`` (mixed, *optional*)

* ``replace`` (mixed)




.. _`TYPO3 Fluid ViewHelper Reference: f:section`:

f:section
---------

A ViewHelper to declare sections in templates for later use with e.g. the ``f:render`` ViewHelper.

Examples
========

Rendering sections
------------------

::

    <f:section name="someSection">This is a section. {foo}</f:section>
    <f:render section="someSection" arguments="{foo: someVariable}" />

Output::

    the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}

Rendering recursive sections
----------------------------

::

    <f:section name="mySection">
       <ul>
            <f:for each="{myMenu}" as="menuItem">
                 <li>
                   {menuItem.text}
                   <f:if condition="{menuItem.subItems}">
                       <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
                   </f:if>
                 </li>
            </f:for>
       </ul>
    </f:section>
    <f:render section="mySection" arguments="{myMenu: menu}" />

Output::

    <ul>
        <li>menu1
            <ul>
                <li>menu1a</li>
                <li>menu1b</li>
            </ul>
        </li>
    [...]
    (depending on the value of {menu})

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\SectionViewHelper




Arguments
*********

* ``name`` (string): Name of the section




.. _`TYPO3 Fluid ViewHelper Reference: f:spaceless`:

f:spaceless
-----------

Space Removal ViewHelper

Removes redundant spaces between HTML tags while
preserving the whitespace that may be inside HTML
tags. Trims the final result before output.

Heavily inspired by Twig's corresponding node type.

Usage of f:spaceless
====================

::

    <f:spaceless>
        <div>
            <div>
                <div>text

        text</div>
            </div>
        </div>
    </f:spaceless>

Output::

    <div><div><div>text

    text</div></div></div>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\SpacelessViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:split`:

f:split
-------

The SplitViewHelper splits a string by the specified separator, which
results in an array. The number of values in the resulting array can
be limited with the limit parameter, which results in an array where
the last item contains the remaining unsplit string.

This ViewHelper mimicks PHP's :php:`explode()` function.


Examples
========

Split with a separator
-----------------------
::

   <f:split value="1,5,8" separator="," />

.. code-block:: text

   {0: '1', 1: '5', 2: '8'}


Split using tag content as value
--------------------------------

::

   <f:split separator="-">1-5-8</f:split>

.. code-block:: text

   {0: '1', 1: '5', 2: '8'}


Split with a limit
-------------------

::

   <f:split value="1,5,8" separator="," limit="2" />

.. code-block:: text

   {0: '1', 1: '5,8'}

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\SplitViewHelper




Arguments
*********

* ``value`` (string, *optional*): The string to explode

* ``separator`` (string): Separator string to explode with

* ``limit`` (int, *optional*): If limit is positive, a maximum of $limit items will be returned. If limit is negative, all items except for the last $limit items will be returned. 0 will be treated as 1.




.. _`TYPO3 Fluid ViewHelper Reference: f:switch`:

f:switch
--------

Switch ViewHelper which can be used to render content depending on a value or expression.
Implements what a basic PHP ``switch()`` does.

An optional default case can be specified which is rendered if none of the
``case`` conditions matches.

Using this ViewHelper can be a sign of weak architecture. If you end up using it extensively
you might want to consider restructuring your controllers/actions and/or use partials and sections.
E.g. the above example could be achieved with :html:`<f:render partial="title.{person.gender}" />`
and the partials "title.male.html", "title.female.html", ...
Depending on the scenario this can be easier to extend and possibly contains less duplication.

Examples
========

Simple Switch statement
-----------------------

::

    <f:switch expression="{person.gender}">
        <f:case value="male">Mr.</f:case>
        <f:case value="female">Mrs.</f:case>
        <f:defaultCase>Mr. / Mrs.</f:defaultCase>
    </f:switch>

Output::

    "Mr.", "Mrs." or "Mr. / Mrs." (depending on the value of {person.gender})

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\SwitchViewHelper




Arguments
*********

* ``expression`` (mixed): Expression to switch




.. _`TYPO3 Fluid ViewHelper Reference: f:then`:

f:then
------

``f:then`` only has an effect inside of ``f:if``. See the ``f:if`` ViewHelper for documentation.

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\ThenViewHelper





.. _`TYPO3 Fluid ViewHelper Reference: f:variable`:

f:variable
----------

Variable assigning ViewHelper

Assigns one template variable which will exist also
after the ViewHelper is done rendering, i.e. adds
template variables.

If you require a variable assignment which does not
exist in the template after a piece of Fluid code
is rendered, consider using ``f:alias`` ViewHelper instead.

Usages:

::

    {f:variable(name: 'myvariable', value: 'some value')}
    <f:variable name="myvariable">some value</f:variable>
    {oldvariable -> f:format.htmlspecialchars() -> f:variable(name: 'newvariable')}
    <f:variable name="myvariable"><f:format.htmlspecialchars>{oldvariable}</f:format.htmlspecialchars></f:variable>

:Implementation: TYPO3Fluid\\Fluid\\ViewHelpers\\VariableViewHelper




Arguments
*********

* ``value`` (mixed, *optional*): Value to assign. If not in arguments then taken from tag content

* ``name`` (string): Name of variable to create




