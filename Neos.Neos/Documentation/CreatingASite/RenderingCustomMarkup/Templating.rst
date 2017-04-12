.. ATTENTION - this is a copy of the templating section of the flow documentation
.. all changes should be applied there first and afterwards be copied here.

.. _templating:

==========
Templating
==========

.. sectionauthor:: Sebastian Kurfürst <sebastian@typo3.org>

.. in this template, the default highlighter is XML:

.. highlight:: xml

Templating is done in *Fluid*, which is a next-generation templating engine. It
has several goals in mind:

* Simplicity
* Flexibility
* Extensibility
* Ease of use

This templating engine should not be bloated, instead, we try to do it "The Zen
Way" - you do not need to learn too many things, thus you can concentrate on getting
your things done, while the template engine handles everything you do not want to
care about.

What Does it Do?
================

In many MVC systems, the view currently does not have a lot of functionality. The
standard view usually provides a ``render`` method, and nothing more. That makes it
cumbersome to write powerful views, as most designers will not write PHP code.

That is where the Template Engine comes into play: It "lives" inside the View, and
is controlled by a special TemplateView which instantiates the Template Parser,
resolves the template HTML file, and renders the template afterwards.

Below, you'll find a snippet of a real-world template displaying a list of blog
postings. Use it to check whether you find the template language intuitive::

	{namespace f=Neos\FluidAdaptor\ViewHelpers}
	<html>
	<head><title>Blog</title></head>
	<body>
	<h1>Blog Postings</h1>
	<f:for each="{postings}" as="posting">
	  <h2>{posting.title}</h2>
	  <div class="author">{posting.author.name} {posting.author.email}</div>
	  <p>
	    <f:link.action action="details" arguments="{id : posting.id}">
	      {posting.teaser}
	    </f:link.action>
	  </p>
	</f:for>
	</body>
	</html>

* The *Namespace Import* makes the ``\Neos\FluidAdaptor\ViewHelper`` namespace available
  under the shorthand f.
* The ``<f:for>`` essentially corresponds to ``foreach ($postings as $posting)`` in PHP.
* With the dot-notation (``{posting.title}`` or ``{posting.author.name}``), you
  can traverse objects. In the latter example, the system calls ``$posting->getAuthor()->getName()``.
* The ``<f:link.action />`` tag is a so-called ViewHelper. It calls arbitrary PHP
  code, and in this case renders a link to the "details"-Action.

There is a lot more to show, including:

* Layouts
* Custom View Helpers
* Boolean expression syntax

We invite you to explore Fluid some more, and please do not hesitate to give feedback!

Basic Concepts
==============

This section describes all basic concepts available. This includes:

* Namespaces
* Variables / Object Accessors
* View Helpers
* Arrays

Namespaces
----------

Fluid can be extended easily, thus it needs a way to tell where a certain tag
is defined. This is done using namespaces, closely following the well-known
XML behavior.

Namespaces can be defined in a template in two ways:

{namespace f=Neos\FluidAdaptor\ViewHelpers}
  This is a non-standard way only understood by Fluid. It links the ``f``
  prefix to the PHP namespace ``\Neos\FluidAdaptor\ViewHelpers``.
<html xmlns:foo=”http://some/unique/namespace”>
  The standard for declaring a namespace in XML. This will link the ``foo``
  prefix to the URI ``http://some/unique/namespace`` and Fluid can look up
  the corresponding PHP namespace in your settings (so this is a two-piece
  configuration). This makes it possible for your XML editor to validate the
  template files and even use an XSD schema for auto completion.

A namespace linking ``f`` to ``\Neos\FluidAdaptor\ViewHelpers`` is imported by
default. All other namespaces need to be imported explicitly.

If using the XML namespace syntax the default pattern
``http://typo3.org/ns/<php namespace>`` is resolved automatically by the
Fluid parser. If you use a custom XML namespace URI you need to configure the
URI to PHP namespace mapping. The YAML syntax for that is:

.. code-block:: yaml

	Neos:
	  Fluid:
	    namespaces:
	      'http://some/unique/namespace': 'My\Php\Namespace'

Variables and Object Accessors
------------------------------

A templating system would be quite pointless if it was not possible to display some
external data in the templates. That's what variables are for.

Suppose you want to output the title of your blog, you could write the following
snippet into your controller:

.. code-block:: php

	$this->view->assign('blogTitle', $blog->getTitle());

Then, you could output the blog title in your template with the following snippet::

	<h1>This blog is called {blogTitle}</h1>

Now, you might want to extend the output by the blog author as well. To do this,
you could repeat the above steps, but that would be quite inconvenient and hard to read.

.. Note::

	The semantics between the controller and the view should be the following:
	The controller instructs the view to "render the blog object given to it",
	and not to "render the Blog title, and the blog posting 1, ...".

	Passing objects to the view instead of simple values is highly encouraged!

That is why the template language has a special syntax for object access. A nicer
way of expressing the above is the following:

.. code-block:: php

	// This should go into the controller:
	$this->view->assign('blog', $blog);

.. code-block:: xml

	<!-- This should go into the template: -->
	<h1>This blog is called {blog.title}, written by {blog.author}</h1>

Instead of passing strings to the template, we are passing whole objects around
now - which is much nicer to use both from the controller and the view side. To
access certain properties of these objects, you can use Object Accessors. By writing
``{blog.title}``, the template engine will call a ``getTitle()`` method on the blog
object, if it exists. Besides, you can use that syntax to traverse associative arrays
and public properties.

.. Tip::

	Deep nesting is supported: If you want to output the email address of the blog
	author, then you can use ``{blog.author.email}``, which is roughly equivalent
	to ``$blog->getAuthor()->getEmail()``.

View Helpers
------------

All output logic is placed in View Helpers.

The view helpers are invoked by using XML tags in the template, and are implemented
as PHP classes (more on that later).

This concept is best understood with an example::

	{namespace f=Neos\FluidAdaptor\ViewHelpers}
	<f:link.action controller="Administration">Administration</f:link.action>

The example consists of two parts:

* *Namespace Declaration* as explained earlier.
* *Calling the View Helper* with the ``<f:link.action...> ... </f:link.action>``
  tag renders a link.

Now, the main difference between Fluid and other templating engines is how the
view helpers are implemented: For each view helper, there exists a corresponding
PHP class. Let's see how this works for the example above:

The ``<f:link.action />`` tag is implemented in the class ``\Neos\FluidAdaptor\ViewHelpers\Link\ActionViewHelper``.

.. note::

	The class name of such a view helper is constructed for a given tag as follows:

	#. The first part of the class name is the namespace which was imported (the namespace
	   prefix ``f`` was expanded to its full namespace ``Neos\FluidAdaptor\ViewHelpers``)
	#. The unqualified name of the tag, without the prefix, is capitalized (``Link``),
	   and the postfix ViewHelper is appended.

The tag and view helper concept is the core concept of Fluid. All output logic is
implemented through such ViewHelpers / tags! Things like ``if/else``, ``for``, … are
all implemented using custom tags - a main difference to other templating languages.

.. note::

	Some benefits of the class-based approach approach are:

	* You cannot override already existing view helpers by accident.
	* It is very easy to write custom view helpers, which live next to the standard view helpers
	* All user documentation for a view helper can be automatically generated from the
	  annotations and code documentation.

Most view helpers have some parameters. These can be plain strings, just like in
``<f:link.action controller="Administration">...</f:link.action>``, but as well
arbitrary objects. Parameters of view helpers will just be parsed with the same rules
as the rest of the template, thus you can pass arrays or objects as parameters.

This is often used when adding arguments to links::

	<f:link.action controller="Blog" action="show" arguments="{singleBlog: blogObject}">
	  ... read more
	</f:link.action>

Here, the view helper will get a parameter called ``arguments`` which is of type ``array``.

.. warning::

	Make sure you do not put a space before or after the opening or closing
	brackets of an array. If you type ``arguments=" {singleBlog : blogObject}"``
	(notice the space before the opening curly bracket), the array is automatically
	casted to a string (as a string concatenation takes place).

	This also applies when using object accessors: ``<f:do.something with="{object}" />``
	and ``<f:do.something with=" {object}" />`` are substantially different: In
	the first case, the view helper will receive an object as argument, while in
	the second case, it will receive a string as argument.

	This might first seem like a bug, but actually it is just consistent that it
	works that way.

Boolean Expressions
-------------------

Often, you need some kind of conditions inside your template. For them, you will
usually use the ``<f:if>`` ViewHelper. Now let's imagine we have a list of blog
postings and want to display some additional information for the currently selected
blog posting. We assume that the currently selected blog is available in ``{currentBlogPosting}``.
Now, let's have a look how this works::

	<f:for each="{blogPosts}" as="post">
	  <f:if condition="{post} == {currentBlogPosting}">... some special output here ...</f:if>
	</f:for>

In the above example, there is a bit of new syntax involved: ``{post} == {currentBlogPosting}``.
Intuitively, this says "if the post I''m currently iterating over is the same as
currentBlogPosting, do something."

Why can we use this boolean expression syntax? Well, because the ``IfViewHelper``
has registered the argument condition as ``boolean``. Thus, the boolean expression
syntax is available in all arguments of ViewHelpers which are of type ``boolean``.

All boolean expressions have the form ``X <comparator> Y``, where:

* *<comparator>* is one of the following: ``==, >, >=, <, <=, % (modulo)``
* *X* and *Y* are one of the following:

  * a number (integer or float)
  * a string (in single or double quotes)
  * a JSON array
  * a ViewHelper
  * an Object Accessor (this is probably the most used example)
  * inline notation for ViewHelpers

Inline Notation for ViewHelpers
-------------------------------

In many cases, the tag-based syntax of ViewHelpers is really intuitive, especially
when building loops, or forms. However, in other cases, using the tag-based syntax
feels a bit awkward -- this can be demonstrated best with the ``<f:uri.resource>``-
ViewHelper, which is used to reference static files inside the *Public/* folder of
a package. That's why it is often used inside ``<style>`` or ``<script>``-tags,
leading to the following code::

	<link rel="stylesheet" href="<f:uri.resource path='myCssFile.css' />" />

You will notice that this is really difficult to read, as two tags are nested into
each other. That's where the inline notation comes into play: It allows the usage
of ``{f:uri.resource()}`` instead of ``<f:uri.resource />``. The above example can
be written like the following::

	<link rel="stylesheet" href="{f:uri.resource(path:'myCssFile.css')}" />

This is readable much better, and explains the intent of the ViewHelper in a much
better way: It is used like a helper function.

The syntax is still more flexible: In real-world templates, you will often find
code like the following, formatting a ``DateTime`` object (stored in ``{post.date}``
in the example below)::

	<f:format.date format="d-m-Y">{post.date}</f:format.date>

This can also be re-written using the inline notation::

	{post.date -> f:format.date(format:'d-m-Y')}

This is also a lot better readable than the above syntax.

.. tip::

	This can also be chained indefinitely often, so one can write::

		{post.date -> foo:myHelper() -> bar:bla()}

	Sometimes you'll still need to further nest ViewHelpers, that is when the design
	of the ViewHelper does not allow that chaining or provides further arguments. Have
	in mind that each argument itself is evaluated as Fluid code, so the following
	constructs are also possible::

		{foo: bar, baz: '{planet.manufacturer -> f:someother.helper(test: \'stuff\')}'}
		{some: '{f:format.stuff(arg: \'foo'\)}'}

To wrap it up: Internally, both syntax variants are handled equally, and every
ViewHelper can be called in both ways. However, if the ViewHelper "feels" like a
tag, use the tag-based notation, if it "feels" like a helper function, use the
Inline Notation.

Arrays
------

Some view helpers, like the ``SelectViewHelper`` (which renders an HTML select
dropdown box), need to get associative arrays as arguments (mapping from internal
to displayed name). See the following example for how this works::

	<f:form.select options="{edit: 'Edit item', delete: 'Delete item'}" />

The array syntax used here is very similar to the JSON object syntax. Thus, the
left side of the associative array is used as key without any parsing, and the
right side can be either:

* a number::

	{a : 1,
	 b : 2
	}

* a string; Needs to be in either single- or double quotes. In a double-quoted
  string, you need to escape the ``"`` with a ``\`` in front (and vice versa for single
  quoted strings). A string is again handled as Fluid Syntax, this is what you
  see in example ``c``::

	{a : 'Hallo',
	 b : "Second string with escaped \" (double quotes) but not escaped ' (single quotes)"
	 c : "{firstName} {lastName}"
	}

* a boolean, best represented with their integer equivalents::

	{a : 'foo',
	 notifySomebody: 1
	 useLogging: 0
	}

* a nested array::

	{a : {
		a1 : "bla1",
		a2 : "bla2"
	  },
	 b : "hallo"
	}

* a variable reference (=an object accessor)::

	{blogTitle : blog.title,
	 blogObject: blog
	}

.. Note::

	All these array examples will result into an associative array. If you have to supply
	a non-associative, i.e. numerically-indexed array, you'll write ``{0: 'foo', 1: 'bar', 2: 'baz'}``.


Passing Data to the View
========================

You can pass arbitrary objects to the view, using ``$this->view->assign($identifier, $object)``
from within the controller. See the above paragraphs about Object Accessors for details
how to use the passed data.

Layouts
=======

In almost all web applications, there are many similarities between each page.
Usually, there are common templates or menu structures which will not change for
many pages.

To make this possible in Fluid, we created a layout system, which we will
introduce in this section.

Writing a Layout
----------------

Every layout is placed in the *Resources/Private/Layouts* directory, and has the
file ending of the current format (by default *.html*). A layout is a normal Fluid
template file, except there are some parts where the actual content of the target
page should be inserted::

	<html>
	<head><title>My fancy web application</title></head>
	<body>
	<div id="menu">... menu goes here ...</div>
	<div id="content">
	  <f:render section="content" />
	</div>
	</body>
	</html>

With this tag, a section from the target template is rendered.

Using a Layout
--------------

Using a layout involves two steps:

* Declare which layout to use: ``<f:layout name="..." />`` can be written anywhere
  on the page (though we suggest to write it on top, right after the namespace
  declaration) - the given name references the layout.
* Provide the content for all sections used by the layout using the ``<f:section>...</f:section>``
  tag: ``<f:section name="content">...</f:section>``

For the above layout, a minimal template would look like the following::

	<f:layout name="example.html" />

	<f:section name="content">
	  This HTML here will be outputted to inside the layout
	</f:section>

Writing Your Own ViewHelper
===========================

As we have seen before, all output logic resides in View Helpers. This includes
the standard control flow operators such as if/else, HTML forms, and much more.
This is the concept which makes Fluid extremely versatile and extensible.

If you want to create a view helper which you can call from your template (as a
tag), you just write a plain PHP class which needs to inherit from
``Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper`` (or its subclasses). You need to implement
only one method to write a view helper:

.. code-block:: php

	public function render()

Rendering the View Helper
-------------------------

We refresh what we have learned so far: When a user writes something like
``<blog:displayNews />`` inside a template (and has imported the ``blog`` namespace
to ``TYPO3\Blog\ViewHelpers``), Fluid will automatically instantiate the class
``TYPO3\Blog\ViewHelpers\DisplayNewsViewHelper``, and invoke the render() method on it.

This ``render()`` method should return the rendered content as string.

You have the following possibilities to access the environment when rendering your view helper:

* ``$this->arguments`` is an associative array where you will find the values for
  all arguments you registered previously.
* ``$this->renderChildren()`` renders everything between the opening and closing
  tag of the view helper and returns the rendered result (as string).
* ``$this->templateVariableContainer`` is an instance of ``Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer``,
  with which you have access to all variables currently available in the template,
  and can modify the variables currently available in the template.

.. Note::

	If you add variables to the ``TemplateVariableContainer``, make sure to remove
	every variable which you added again. This is a security measure against side-effects.

	It is also not possible to add a variable to the TemplateVariableContainer if
	a variable of the same name already exists - again to prevent side effects and
	scope problems.

Implementing a ``for`` ViewHelper
---------------------------------

Now, we will look at an example: How to write a view helper giving us the ``foreach``
functionality of PHP.

A loop could be called within the template in the following way::

	<f:for each="{blogPosts}" as="blogPost">
	  <h2>{blogPost.title}</h2>
	</f:for>

So, in words, what should the loop do?

It needs two arguments:

* ``each``: Will be set to some object or array which can be iterated over.
* ``as``: The name of a variable which will contain the current element being iterated over

It then should do the following (in pseudo code):

.. code-block:: php

	foreach ($each as $$as) {
	  // render everything between opening and closing tag
	}

Implementing this is fairly straightforward, as you will see right now:

.. code-block:: php

	class ForViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {

	  /**
	   * Renders a loop
	   *
	   * @param array $each Array to iterate over
	   * @param string $as Iteration variable
	   */
	  public function render(array $each, $as) {
		$out = '';
		foreach ($each as $singleElement) {
		  $this->variableContainer->add($as, $singleElement);
		  $out .= $this->renderChildren();
		  $this->variableContainer->remove($as);
		}
		return $out;
	  }

	}

* The PHPDoc is part of the code! Fluid extracts the argument data types from the PHPDoc.
* You can simply register arguments to the view helper by adding them as method
  arguments of the ``render()`` method.
* Using ``$this->renderChildren()``, everything between the opening and closing
  tag of the view helper is rendered and returned as string.

Declaring Arguments
-------------------

We have now seen that we can add arguments just by adding them as method arguments
to the ``render()`` method. There is, however, a second method to register arguments.

You can also register arguments inside a method called ``initializeArguments()``.
Call ``$this->registerArgument($name, $dataType, $description, $isRequired, $defaultValue=NULL)`` inside.

It depends how many arguments a view helper has. Sometimes, registering them as
``render()`` arguments is more beneficial, and sometimes it makes more sense to
register them in ``initializeArguments()``.

AbstractTagBasedViewHelper
--------------------------

Many view helpers output an HTML tag - for example ``<f:link.action ...>`` outputs
a ``<a href="...">`` tag. There are many ViewHelpers which work that way.

Very often, you want to add a CSS class or a target attribute to an ``<a href="...">``
tag. This often leads to repetitive code like below. (Don't look at the code too
thoroughly, it should just demonstrate the boring and repetitive task one would
have without the ``AbstractTagBasedViewHelper``):

.. code-block:: php

	class ActionViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {

	  public function initializeArguments() {
		$this->registerArgument('class', 'string', 'CSS class to add to the link');
		$this->registerArgument('target', 'string', 'Target for the link');
		... and more ...
	  }

	  public function render() {
		$output = '<a href="..."';
		if ($this->arguments['class']) {
		  $output .= ' class="' . $this->arguments['class'] . '"';
		}
		if ($this->arguments['target']) {
		  $output .= ' target="' . $this->arguments['target'] . '"';
		}
		$output .= '>';
		... and more ...
		return $output;
	  }

	}

Now, the ``AbstractTagBasedViewHelper`` introduces two more methods you can use
inside ``initializeArguments()``:

* ``registerTagAttribute($name, $type, $description, $required)``: Use this method
  to register an attribute which should be directly added to the tag.
* ``registerUniversalTagAttributes()``: If called, registers the standard HTML
  attributes ``class, id, dir, lang, style, title``.

Inside the ``AbstractTagBasedViewHelper``, there is a ``TagBuilder`` available
(with ``$this->tag``) which makes building a tag a lot more straightforward.

With the above methods, the ``Link\ActionViewHelper`` from above can be condensed as follows:

.. code-block:: php

	class ActionViewHelper extends \TYPO3\Fluid\Core\AbstractViewHelper {

		public function initializeArguments() {
			$this->registerUniversalTagAttributes();
		}

		/**
		 * Render the link.
		 *
		 * @param string $action Target action
		 * @param array $arguments Arguments
		 * @param string $controller Target controller. If NULL current controllerName is used
		 * @param string $package Target package. if NULL current package is used
		 * @param string $subpackage Target subpackage. if NULL current subpackage is used
		 * @param string $section The anchor to be added to the URI
		 * @return string The rendered link
		 */
		public function render($action = NULL, array $arguments = array(),
		                       $controller = NULL, $package = NULL, $subpackage = NULL,
			                   $section = '') {
			$uriBuilder = $this->controllerContext->getURIBuilder();
			$uri = $uriBuilder->uriFor($action, $arguments, $controller, $package, $subpackage, $section);
			$this->tag->addAttribute('href', $uri);
			$this->tag->setContent($this->renderChildren());

			return $this->tag->render();
		}

	}

Additionally, we now already have support for all universal HTML attributes.

.. tip::

	The ``TagBuilder`` also makes sure that all attributes are escaped properly,
	so to decrease the risk of Cross-Site Scripting attacks, make sure to use it
	when building tags.

additionalAttributes
~~~~~~~~~~~~~~~~~~~~

Sometimes, you need some HTML attributes which are not part of the standard.
As an example: If you use the Dojo JavaScript framework, using these non-standard
attributes makes life a lot easier.

We think that the templating framework should not constrain the user in his
possibilities -- thus, it should be possible to add custom HTML attributes as well,
if they are needed. Our solution looks as follows:

Every view helper which inherits from ``AbstractTagBasedViewHelper`` has a special
argument called ``additionalAttributes`` which allows you to add arbitrary HTML
attributes to the tag.

If the link tag from above needed a new attribute called ``fadeDuration``, which
is not part of HTML, you could do that as follows:

.. code-block:: xml

	<f:link.action action="..." additionalAttributes="{fadeDuration : 800}">
		Link with fadeDuration set
	</f:link.action>

This attribute is available in all tags that inherit from ``Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper``.

AbstractConditionViewHelper
---------------------------

If you want to build some kind of ``if/else`` condition, you should base the ViewHelper
on the ``AbstractConditionViewHelper``, as it gives you convenient methods to render
the ``then`` or ``else`` parts of a ViewHelper. Let's look at the ``<f:if>``-ViewHelper
for a usage example, which should be quite self-explanatory:

.. code-block:: php

	class IfViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper {

		/**
		 * renders <f:then> child if $condition is true, otherwise renders <f:else> child.
		 *
		 * @param boolean $condition View helper condition
		 * @return string the rendered string
		 */
		public function render($condition) {
			if ($condition) {
				return $this->renderThenChild();
			} else {
				return $this->renderElseChild();
			}
		}

	}

By basing your condition ViewHelper on the ``AbstractConditionViewHelper``,
you will get the following features:

* Two API methods ``renderThenChild()`` and ``renderElseChild()``, which should be
  used in the ``then`` / ``else`` case.
* The ViewHelper will have two arguments defined, called ``then`` and ``else``,
  which are very helpful in the Inline Notation.
* The ViewHelper will automatically work with the ``<f:then>`` and ``<f:else>``-Tags.

Widgets
=======

Widgets are special ViewHelpers which encapsulate complex functionality. It can
be best understood what widgets are by giving some examples:

* ``<f:widget.paginate>`` renders a paginator, i.e. can be used to display large
  amounts of objects. This is best known from search engine result pages.
* ``<f:widget.autocomplete>`` adds autocompletion functionality to a text field.
* More widgets could include a Google Maps widget, a sortable grid, ...

Internally, widgets consist of an own Controller and View.

Using Widgets
-------------

Using widgets inside your templates is really simple: Just use them like standard
ViewHelpers, and consult their documentation for usage examples. An example for
the ``<f:widget.paginate>`` follows below::

	<f:widget.paginate objects="{blogs}" as="paginatedBlogs" configuration="{itemsPerPage: 10}">
	  // use {paginatedBlogs} as you used {blogs} before, most certainly inside
	  // a <f:for> loop.
	</f:widget.paginate>

In the above example, it looks like ``{blogs}`` contains all ``Blog`` objects, thus
you might wonder if all objects were fetched from the database. However, the blogs
are *not fetched* from the database until you actually use them, so the Paginate Widget
will adjust the query sent to the database and receive only the small subset of objects.

So, there is no negative performance overhead in using the Paginate Widget.

Writing widgets
---------------

We already mentioned that a widget consists of a controller and a view, all triggered
by a ViewHelper. We'll now explain these different components one after each other,
explaining the API you have available for creating your own widgets.

ViewHelper
~~~~~~~~~~

All widgets inherit from ``Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper``.
The ViewHelper of the widget is the main entry point; it controls the widget and
sets necessary configuration for the widget.

To implement your own widget, the following things need to be done:

* The controller of the widget needs to be injected into the ``$controller`` property.
* Inside the ``render()``-method, you should call ``$this->initiateSubRequest()``,
  which will initiate a request to the controller which is set in the ``$controller``
  property, and return the ``Response`` object.
* By default, all ViewHelper arguments are stored as *Widget Configuration*, and
  are also available inside the Widget Controller. However, to modify the Widget
  Configuration, you can override the ``getWidgetConfiguration()`` method and return
  the configuration which you need there.

There is also a property ``$ajaxWidget``, which we will explain later in :ref:`ajax-widgets`.

Controller
----------

A widget contains one controller, which must inherit from ``Neos\FluidAdaptor\Core\Widget\AbstractWidgetController``,
which is an ``ActionController``. There is only one difference between the normal
``ActionController`` and the ``AbstractWidgetController``: There is a property
``$widgetConfiguration``, containing the widget's configuration which was set in the ViewHelper.

Fluid Template
--------------

The Fluid templates of a widget are normal Fluid templates as you know them, but
have a few ViewHelpers available additionally:

<f:uri.widget>
  Generates an URI to another action of the widget.
<f:link.widget>
  Generates a link to another action of the widget.
<f:renderChildren>
  Can be used to render the child nodes of the Widget ViewHelper,
  possibly with some more variables declared.

.. _ajax-widgets:

Ajax Widgets
------------

Widgets have special support for AJAX functionality. We'll first explain what needs
to be done to create an AJAX compatible widget, and then explain it with an example.

To make a widget AJAX-aware, you need to do the following:

* Set ``$ajaxWidget`` to TRUE inside the ViewHelper. This will generate an unique
  AJAX Identifier for the Widget, and store the WidgetConfiguration in the user's
  session on the server.
* Inside the index-action of the Widget Controller, generate the JavaScript which
  triggers the AJAX functionality. There, you will need a URI which returns the
  AJAX response. For that, use the following ViewHelper inside the template::

	<f:uri.widget ajax="TRUE" action="..." arguments="..." />

* Inside the template of the AJAX request, ``<f:renderChildren>`` is not available,
  because the child nodes of the Widget ViewHelper are not accessible there.

XSD schema generation
=====================

A XSD schema file for your ViewHelpers can be created by executing

.. code-block:: text

	./flow documenation:generatexsd <Your>\\<Package>\\ViewHelpers
		--target-file /some/directory/your.package.xsd

Then import the XSD file in your favorite IDE and map it to the namespace
``http://typo3.org/ns/<Your/Package>/ViewHelpers``. Add the namespace to your
Fluid template by adding the ``xmlns`` attribute to the root tag (usually
``<xml …>`` or ``<html …>``).

.. note::

	You are able to use a different XML namespace pattern by specifying the
	``-–xsd-namespace argument`` in the generatexsd command.

If you want to use this inside partials, you can use the “section” argument of
the render ViewHelper in order to only render the content of the partial.

Partial::

	<html xmlns:x=”http://typo3.org/ns/Your/Package/ViewHelpers”>
	<f:section name=”content”>
		<x:yourViewHelper />
	</f:section>

Template::

	<f:render partial=”PartialName” section=”content” />
