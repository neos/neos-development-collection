========================================
Creating your first TYPO3 Phoenix plugin
========================================

Any FLOW3 package can be used as a plugin with a little effort. This section
will guide you through a simple example. First, we will create a really basic
FLOW3 package. Second, we'll expose this FLOW3 package as Phoenix plugin.

Create a FLOW3 package
======================

First create a package with a model, so we have something to show in the
plugin:

.. code-block:: bash

  ./flow3 kickstart:package Sarkosh.CdCollection
  ./flow3 kickstart:model Sarkosh.CdCollection Album title:string year:integer description:string rating:integer
  ./flow3 kickstart:repository Sarkosh.CdCollection Album

Then generate a migration to create the needed DB schema:

.. code-block:: bash

  ./flow3 doctrine:migrationgenerate
  mkdir -p Packages/Application/Sarkosh.CdCollection/Migrations/Mysql
  mv Data/DoctrineMigrations/Version<timestamp>.php Packages/Application/Sarkosh.CdCollection/Migrations/Mysql/
  ./flow3 doctrine:migrate

You should now have a package with a default controller and templates created.
In order to view them you can call the frontend like
``http://phoenix.local/sarkosh.cdcollection``, but you need to include the
FLOW3 default routes first (add them before the Phoenix routes):

.. code-block:: yaml

  ##
  # FLOW3 subroutes
  #

  -
    name: 'FLOW3'
    uriPattern: '<FLOW3Subroutes>'
    defaults:
      '@format': 'html'
    subRoutes:
      FLOW3Subroutes:
        package: TYPO3.FLOW3

Now you can add some entries for your CD collection in the database::

  INSERT INTO "sarkosh_cdcollection_domain_model_album" (
    "flow3_persistence_identifier", "title", "year", "description", "rating"
  ) VALUES (
    uuid(), 'Jesus Christ Superstar', '1970',
    'Jesus Christ Superstar is a rock opera by Andrew Lloyd Webber, with lyrics by Tim Rice.',
    '5'
  );

(or using your database tool of choice) and adjust the templates so a list of
CDs is shown. When you are done with that, you can make a plugin out of that.

Converting a FLOW3 Package Into a Phoenix Plugin
================================================

To activate a FLOW3 package as Phoenix plugin, you only need to provide two
configuration blocks. First, you need to add a new *content type* for the plugin,
such that the user can choose the plugin from the list of content elements:

Add the following to *Configuration/Settings.yaml* of your package:

.. code-block:: yaml

  TYPO3:
    TYPO3CR:
      contentTypes:
        'Sarkosh.CdCollection:Plugin':
          superTypes: ['TYPO3.TYPO3:Plugin']
          label: 'CD Collection'
          group: 'Plugins'

Second, the rendering of the plugin needs to be specified using TypoScript,
so the following TypoScript needs to be inserted into your package's *Resources/Private/TypoScript/Plugin.ts2*::

  prototype(Sarkosh.CdCollection:Plugin) < prototype(TYPO3.TYPO3:PluginRenderer)
  prototype(Sarkosh.CdCollection:Plugin) {
       package = 'Sarkosh.CdCollection'
       controller = 'Standard'
       action = 'index'
  }

Finally tweak your site package's *Root.ts2* and include the newly created TypoScript file::

  include: resource://Sarkosh.CdCollection/Private/TypoScript/Plugin.ts2

Now log in to your Phoenix backend (remove the FLOW3 routes again now), and you
should be able to add your plugin just like any other content element.
