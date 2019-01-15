========================
Build your own Generator
========================

Implement your own generator
============================

To implement your own Generator, first check the code of the Generators included in the Media package.

Basically, you need to extend ``AbstractThumbnailGenerator`` and implement the ``ThumbnailGeneratorInterface::refresh()``
method. The ``refresh`` method receives a ``Thumbnail`` object, based on this object do the required processing to
generate a thumbnail. In most cases the Thumbnail can be persisted by attaching the new resource to the ``Thumbnail``
object.

Determine if a Generator can handle the current Thumbnail
---------------------------------------------------------

You can also implement the ``ThumbnailGeneratorInterface::canRefresh()`` if your Generator has some specific
requirements (like maximum file size, MIME type, external service availability, etc.).

Priority
--------

The ``ThumbnailGeneratorStrategy`` choose the Generator by two factors, the value of the static property ``priority`` and
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

In your generator class use the ``AbstractThumbnailGenerator::getOption()`` to access your settings::

    Neos:
      Media:
        thumbnailGenerators:
          'Your\Package\Domain\Model\ThumbnailGenerator\YourOwnThumbnailGenerator':
            priority: 100
            parameterOne: 100
            parameterTwo: 200

Remember to add the Media Package in your package ``composer.json``` to load the Media package before your own::

    {
        ...
        "require": {
            "neos/flow": "*",
            "neos/media": "*"
        }
        ...
    }

Community supported Generators
==============================

* `FilePreviews <https://github.com/ttreeagency/FilePreviews>`_

    Can be use to integrate the service from `filepreviews.io <http://filepreviews.io/>`_ in your project and generate
    thumbnail for Office or Audio documents.

Feel free to contact us at hello@neos.io, if you publish some Generators under an open-source licence.
