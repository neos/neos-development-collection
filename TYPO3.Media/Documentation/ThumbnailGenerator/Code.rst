========================
Build your own Generator
========================

Implement your own generator
============================

To implement your own Generator, first check the code of the Generators included in the Media package.

Basically, you need to extend ``AbstractThumbnailGenerator`` and implement the ``ThumbnailGeneratorInterface::refresh()``
method. The ``refresh`` method receive a ``Thumbnail`` object, based on this object do the required processing to
generate a thumbnail. In most case the Thumbnail can be persisted by attaching the new resource to the ``Thumbnail``
object.

Transiant Thumbnail
-------------------

Sometimes, the persistence is not required, in this case you can mark the Thumbnail as transient, by calling the
method ``Thumbnail::setTransiant(true)``, check the SVG or Icon Generator for example of Transiant Thumbnail.

Programmatically check if a Generator can handle the current Thumbnail
----------------------------------------------------------------------

You can also implement the ``ThumbnailGeneratorInterface::canRefresh()`` if your Generator has some specific
requirements (like maximum file size, mime type, check external service availability, ...).

Priority
--------

The ``ThumbnailGeneratorStrategy`` choose the Generator by two factor the value of the static property ``priority`` and
the return value of the method ``ThumbnailGeneratorInterface::canRefresh()``. For priority value, higher is better::

    class YourOwnThumbnailGenerator extends AbstractThumbnailGenerator
    {
        /**
         * @var integer
         * @api
         */
        protected static $priority = 100;

    }

You can always override this priority in your ``Settings.yaml``.

Configuration
-------------

In your generator class use the ``AbstractThumbnailGenerator::getOption()`` to access you settings::

    TYPO3:
      Media:
        thumbnailGenerators:
          'Your\Package\Domain\Model\ThumbnailGenerator\YourOwnThumbnailGenerator':
            priority: 100
            parameterOne: 100
            parameterTwo: 200

Don't forget to add the Media Package in your package ``composer.json``` to load the Media package before your own::

    {
        ...
        "require": {
            "typo3/flow": "*",
            "typo3/media": "*"
        }
        ...
    }

Community supported Generators
==============================

* `FilePreviews <https://github.com/ttreeagency/FilePreviews>`_

    Can be use to integrate the service from `filepreviews.io <http://filepreviews.io/>`_ in your project and generate
    thumbnail for Office or Audio documents.

Feel free to contact us at hello@neos.io if you publish some Generators under opensource licences.

