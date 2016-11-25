.. _custom-flowquery-operation:

Custom FlowQuery Operations
===========================

The FlowQuery EelHelper provides you with methods to traverse the ContentRepository. Implementing custom operations
allows the creation of filters, sorting algorithms and much more.

.. warning:: This has not been declared a public api yet and still might change a bit in future release. Nevertheless it
	is an important functionality and this or a similar mechanism will still be available in the future.

Create FlowQuery Operation
--------------------------

Implementing a custom operation is done by extending the ``TYPO3\Eel\FlowQuery\Operations\AbstractOperation`` class.
The Operation is implemented in the evaluate method of that class.

To identify the operation lateron in TypoScript the static class variable ``$shortName`` has to be set.

If you pass arguments to the FlowQuery Operation they end up in the numerical array ``$arguments`` that is handed over
to the evaluate method.

.. code-block:: php

	namespace Vendor\Site\FlowQuery\Operation;

	use TYPO3\Eel\FlowQuery\FlowQuery;
	use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;

	class RandomElementOperation extends AbstractOperation {

		/**
		 * {@inheritdoc}
		 *
		 * @var string
		 */
		static protected $shortName = 'randomElement';

		/**
		 * {@inheritdoc}
		 *
		 * @param FlowQuery $flowQuery the FlowQuery object
		 * @param array $arguments the arguments for this operation
		 * @return void
		 */
		public function evaluate(FlowQuery $flowQuery, array $arguments) {
			$context = $flowQuery->getContext();
			$randomKey = array_rand($context);
			$result = array($context[$randomKey]);
			$flowQuery->setContext($result);
		}
	}

In TypoScript you can use this operation to find a random element of the main ContentCollection of the Site-Node::

	randomStartpageContent = ${q(site).children('main').children().randomElement()}


.. note:: For overriding existing operations another operation with the same shortName but a higher priority
	can be implemented.

Create Final FlowQuery Operations
---------------------------------

If a FlowQuery operation does return a value instead of modifying the FlowQuery Context it has to be declared ``$final``.

.. code-block:: php

	namespace Vendor\Site\FlowQuery\Operation;

	use TYPO3\Eel\FlowQuery\FlowQuery;
	use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;

	class DebugOperation extends AbstractOperation {

		/**
		 * If TRUE, the operation is final, i.e. directly executed.
		 *
		 * @var boolean
		 * @api
		 */
		static protected $final = TRUE;

		/**
		 * {@inheritdoc}
		 *
		 * @param FlowQuery $flowQuery the FlowQuery object
		 * @param array $arguments the arguments for this operation
		 * @return void
		 */
		public function evaluate(FlowQuery $flowQuery, array $arguments) {
			return \TYPO3\Flow\var_dump($flowQuery->getContext(), NULL, TRUE);
		}
	}

Further Reading
---------------

#. For checking that the operation can actually work on the current context a canEvaluate method can be implemented.

#. You sometimes might want to use the Fizzle Filter Engine to use jQuery like selectors in the arguments of your
	operation. Therefore you can apply a filter operation that is applied to the context as follows:
	``$flowQuery->pushOperation('filter', $arguments);``.

