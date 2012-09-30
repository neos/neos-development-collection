========================================
Creating your first TYPO3 Phoenix plugin
========================================

Any TYPO3 Flow package can be used as a plugin with a little effort. This section
will guide you through a simple example. First, we will create a really basic
TYPO3 Flow package. Second, we'll expose this TYPO3 Flow package as Phoenix plugin.

Create a TYPO3 Flow package
===========================

First create a package with a model, so we have something to show in the
plugin:

.. code-block:: bash

  ./flow kickstart:package Sarkosh.CdCollection
  ./flow kickstart:model Sarkosh.CdCollection Album title:string year:integer description:string rating:integer
  ./flow kickstart:repository Sarkosh.CdCollection Album

Then generate a migration to create the needed DB schema:

.. code-block:: bash

  ./flow doctrine:migrationgenerate
  mkdir -p Packages/Application/Sarkosh.CdCollection/Migrations/Mysql
  mv Data/DoctrineMigrations/Version<timestamp>.php Packages/Application/Sarkosh.CdCollection/Migrations/Mysql/
  ./flow doctrine:migrate

You should now have a package with a default controller and templates created.
In order to view them you can call the frontend like
``http://phoenix.local/sarkosh.cdcollection``, but you need to include the
TYPO3 Flow default routes first (add them before the Phoenix routes):

.. code-block:: yaml

  ##
  # TYPO3 Flow subroutes
  #

  -
    name: 'Flow'
    uriPattern: '<FlowSubroutes>'
    defaults:
      '@format': 'html'
    subRoutes:
      FlowSubroutes:
        package: TYPO3.Flow

Now you can add some entries for your CD collection in the database::

  INSERT INTO "sarkosh_cdcollection_domain_model_album" (
    "persistence_object_identifier", "title", "year", "description", "rating"
  ) VALUES (
    uuid(), 'Jesus Christ Superstar', '1970',
    'Jesus Christ Superstar is a rock opera by Andrew Lloyd Webber, with lyrics by Tim Rice.',
    '5'
  );

(or using your database tool of choice) and adjust the templates so a list of
CDs is shown. When you are done with that, you can make a plugin out of that.

Converting a TYPO3 Flow Package Into a Phoenix Plugin
=====================================================

To activate a TYPO3 Flow package as Phoenix plugin, you only need to provide two
configuration blocks. First, you need to add a new *content type* for the plugin,
such that the user can choose the plugin from the list of content elements:

Add the following to *Configuration/Settings.yaml* of your package:

.. code-block:: yaml

  TYPO3:
    TYPO3CR:
      contentTypes:
        'Sarkosh.CdCollection:Plugin':
          superTypes: ['TYPO3.Phoenix.ContentTypes:Plugin']
          label: 'CD Collection'
          group: 'Plugins'

Second, the rendering of the plugin needs to be specified using TypoScript,
so the following TypoScript needs to be inserted into your package's *Resources/Private/TypoScripts/Library/Plugin.ts2*::

  prototype(Sarkosh.CdCollection:Plugin) < prototype(TYPO3.Phoenix.ContentTypes:Plugin)
  prototype(Sarkosh.CdCollection:Plugin) {
       package = 'Sarkosh.CdCollection'
       controller = 'Standard'
       action = 'index'
  }

Finally tweak your site package's *Root.ts2* and include the newly created TypoScript file::

  include: resource://Sarkosh.CdCollection/Private/TypoScripts/Library/Plugin.ts2

Now log in to your Phoenix backend (remove the TYPO3 Flow routes again now), and you
should be able to add your plugin just like any other content element.
