.. _data-sources:

============
Data sources
============

Data sources allow easy integration of data source end points, to provide data to the editing interface without having
to define routes, policies, controller.

Data sources can be used for various purposes, however the return format is restricted to JSON. An example of their
usage is as a data provider for the inspector SelectBoxEditor (see :ref:`property-editor-reference-selectboxeditor`
for details).

A data source is defined by an identifier and this identifier has to be unique.

To implement a data source, create a class that implements ``TYPO3\Neos\Service\DataSource\DataSourceInterface``,
preferably by extending ``TYPO3\Neos\Service\DataSource\AbstractDataSource``. Then set the static protected
property ``identifier`` to a string. Make sure you use a unique identifier, e.g. ``acme-demo-available-dates``.

Then implement the ``getData`` method, with the following signature:

.. code-block:: php

  /**
   * Get data
   *
   * The return value must be JSON serializable data structure.
   *
   * @param NodeInterface $node The node that is currently edited (optional)
   * @param array $arguments Additional arguments (key / value)
   * @return mixed JSON serializable data
   * @api
   */
  public function getData(NodeInterface $node = null, array $arguments);

The return value of the method will be JSON encoded.

Data sources are available with the following URI pattern ``/neos/service/data-source/<identifier>``, which can be linked to
using the follow parameters:

- ``@package``:    'TYPO3.Neos'
- ``@subpackage``: 'Service'
- ``@controller``: 'DataSource'
- ``@action``:     'index
- ``@format``:     'json'
- ``dataSourceIdentifier``: '<identifier>'

Arbitrary additional arguments are allowed. Additionally the routing only accepts ``GET`` requests.

If additional arguments are provided then they will automatically be available in the ``$arguments`` parameter of the
``getData`` method. Additional arguments will not be property mapped, meaning they will contain their plain value.
However if an argument with the key ``node`` is provided, it will automatically be converted into a node. Provide a
valid node path to use this, and keep in mind that the ``node`` argument is restricted to this use-case. This is done
to make working with nodes easy.

The ``dataSourceIdentifier`` will automatically be removed from the ``arguments`` parameter.

.. note::
  Data sources are restricted to only be accessible for users with the ``TYPO3.Neos:Backend.DataSource`` privilege,
  which is included in the ``TYPO3.Neos:Editor`` role. This means that a user has to have access to the backend to
  be able to access a data point.

Example ``TestDataSource.php``:

.. code-block:: php

  <?php
  namespace Acme\YourPackage\DataSource;

  use TYPO3\Neos\Service\DataSource\AbstractDataSource;
  use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

  class TestDataSource extends AbstractDataSource {

      /**
       * @var string
       */
      static protected $identifier = 'acme-yourpackage-test';

      /**
       * Get data
       *
       * @param NodeInterface $node The node that is currently edited (optional)
       * @param array $arguments Additional arguments (key / value)
       * @return array JSON serializable data
       */
      public function getData(NodeInterface $node = NULL, array $arguments)
      {
          return isset($arguments['integers']) ? array(1, 2, 3) : array('a', 'b', 'c');
      }
  }
