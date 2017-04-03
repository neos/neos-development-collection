.. _custom-view-helpers:

Custom ViewHelpers
==================

Custom ViewHelpers are the way to extend the Fluid templating engine to the needs of your project.

.. note:: The full documentation for writing ViewHelpers is included in the `Flow documentation
	<http://flowframework.readthedocs.org/en/stable/>`_ This documentation is a short introduction
	of the basic principles.

Create A ViewHelper Class
-------------------------

If you want to create a ViewHelper that you can call from your template (as a
tag), you write a php class which has to inherit from
``\Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper`` (or its subclasses). You need to implement
only one method to write a view helper:

.. code-block:: php

	namespace Vendor\Site\ViewHelpers;
	class TitleViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {
		public function render() {
			return 'Hello World';
		}
	}

Afterwards you have to register the namespace of your ViewHelper in the template before actually using it:

.. code-block:: xml

	{namespace site=Vendor\Site\ViewHelpers}
	<!-- tag syntax -->
	<site:title />

	<!-- inline syntax -->
	{site:title()}

.. note:: Please look at the :ref:`templating` documentation for an in-depth explanation of Fluid templating.

Declare View Helper Arguments
-----------------------------

There exist two ways to pass arguments to a ViewHelper that can be combined:

#. Add arguments to the render-method of the ViewHelper Class:

	.. code-block:: php

		namespace Vendor\Site\ViewHelpers;

		class TitleViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {
			/**
			 * Render the title and apply some magic
			 *
			 * @param string $title the title
			 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
			 * @return string Translated label or source label / ID key
			 * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
			 */
			public function render($title, $flag = FALSE) {

				# apply magic here ...

				return '<h1>' . $title . '</h1>';
			}
		}

#. Use the registerArgument method of the AbstractViewHelper Class:

	This is especially useful if you have to define lots of arguments or create base classes for derived ViewHelpers.

	.. code-block:: php

		namespace Vendor\Site\ViewHelpers;

		class TitleViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper {

			/**
			 * Initialize arguments
			 *
			 * @return void
			 */
			public function initializeArguments() {
				$this->registerArgument('title', 'string', 'The Title to render');
				$this->registerArgument('flag', 'boolean', 'A ');
			}

			public function render() {
				$title = $this->arguments['title'];
				$flag = $this->arguments['flag'];

				# apply magic here ...

				return '<h1>' . $title . '</h1>';
			}
		}

Context and Children
--------------------

If your ViewHelper contains HTML code and possibly other ViewHelpers as well, the content of the ViewHelper can be rendered and
used for further processing:

.. code-block:: php

	public function render($title = NULL) {
		if ($title === NULL) {
			$title = $this->renderChildren();
		}
		return '<h1>' . $title . '</h1>';
	}

.. note:: It is a good practice to support passing of the main context as argument or children for flexibility an ease of use.

Sometimes your ViewHelper has to interact with other ViewHelpers insider that are rendered via ``$this->renderChildren()``.
To do that you can modify the context for the fluid rendering of the children. That allows keeping the scope of every
ViewHelper clean and the implementation simple.

.. code-block:: php

	public function render() {
		# get the template variable container
		$templateVariableContainer = $renderingContext->getTemplateVariableContainer();
		# add a variable to the context
		$templateVariableContainer->add('salutation', 'Hello World');
		# render the children, the variable salutation is available for the child view helpers
		$result = $this->renderChildren();
		# remove the added variable again from the context
		$templateVariableContainer->remove('salutation');
		return $result;
	}

.. note:: It is a considered a good practice to create a bunch of simple ViewHelpers that interact via Fluid context
	instead of creating complex logic inside a single ViewHelper.

Further reading
---------------

#. TagBased ViewHelpers - For the common case that a ViewHelper renders a single HTML-Tag as a result there
   is a special base class. The TagBased ViewHelper contains automatic security measures, so if you use this,
   the likelyhood of cross-site-scripting vulnerabilities is greatly reduced.

   To find out more about that please lookup ``AbstractTagBasedViewHelper`` in the `Flow documentation
   <http://flowframework.readthedocs.org/en/stable/>`_

#. Condition ViewHelpers - To provide ViewHelpers that are doing either this or that there is a base class ``AbstractConditionViewHelper``.
   This can be used in cases where you cannot express your condition via ``<f:if condition="..." >``.
   To find out more about that please lookup ``AbstractTagBasedViewHelper`` in the Flow-Documentation.

#. Widget ViewHelpers - If a view helper needs complex controller logic, has to interact with repositories to fetch data,
   needs some ajax-interaction or needs a Fluid-Template for rendering, you can create a Fluid Widget.
   It is possible to override the Fluid Template of a Widget in another package so this also provides a way to create
   extensible ViewHelpers.
