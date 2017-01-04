.. _cookbook-translating-content:

===================
Translating content
===================

Translations for content are based around the concept of :ref:`content-dimensions`. The dimension ``language`` can be
used for most translation scenarios. This cookbook shows how to set up the dimension, migrate existing content to use
dimensions and how to work with translations.

Dimension configuration
=======================

The first step is to configure a ``language`` dimension with a *dimension preset* for each language. This should be done
in the file ``Configuration/Settings.yaml`` of your site package:

.. code-block:: yaml

	Neos:
	  ContentRepository:
	    contentDimensions:
	      'language':
	        label: 'Language'
	        icon: 'icon-language'
	        default: 'en'
	        defaultPreset: 'en'
	        presets:
	          'en':
	            label: 'English'
	            values: ['en']
	            uriSegment: 'english'
	          'fr':
	            label: 'Français'
	            values: ['fr', 'en']
	            uriSegment: 'francais'
	          'de':
	            label: 'Deutsch'
	            values: ['de', 'en']
	            uriSegment: 'deutsch'

This will configure a dimension ``language`` with a default dimension value of ``en``, a default preset ``en`` and
some presets for the actual available dimension configurations. Each of these presets represents one language that
is available for display on the website.

As soon as a dimension with presets is configured, the content module will show a dimension selector to select presets
for each dimension. This can be used in combination with a language menu on the website.

.. note:: Neos 1.2 only supports translation of existing content by using *fallbacks*. In the example there is a fallback from
          ``fr`` to ``en`` in the ``fr`` dimension preset. While it is possible to work without a default language and fallbacks,
          no existing content can be translated in this case. This restriction is removed with Neos 1.3.

Migration of existing content
=============================

Existing content of a site needs to be migrated to use the dimension default value, otherwise no nodes would be found.
This can be done with a node migration which is included in the ``Neos.ContentRepository`` package::

	./flow node:migrate 20150716212459

This migration has to be applied whenever a new dimension is configured to set the default value on all existing nodes.

Integrate Language Menu
=======================

A simple language menu can be displayed on the site by using the ``Neos.Neos:DimensionsMenu`` Fusion object::

	page {
	    body {
	        parts {
	            languageMenu = Neos.Neos:DimensionsMenu {
	                dimension = 'language'
	            }
	        }
	    }
	}

This will render a ``<ul>`` with links to node variants in other languages of the current document node with a label
from a dimension preset. Of course the template can be customized for custom output with the ``templatePath`` property.

Working with translated content
===============================

All content that needs to be translated should go into the default preset first. After selecting a different preset
either using the dimension selector or a language menu, the default content will shine through. As soon as a
shine-through node is updated, it will be automatically copied to a new node variant with the most specific dimension
value in the fallback list.
