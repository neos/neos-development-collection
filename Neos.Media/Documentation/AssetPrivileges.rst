================
Asset Privileges
================

Introduction
------------

Asset privileges allows assets to be restricted based on authenticated roles.
This package comes with the following privileges:

Restrict read access to *Assets* based on their *media type*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadAllPDFs':
        matcher: 'hasMediaType("application/pdf")'

Restrict read access to *Assets* based on *Tag*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadConfidentialAssets':
        matcher: 'isTagged("confidential")'

.. note:

  Instead of the *label* the *technical identifier (UUID)* of the tag can be used, too

Restrict read access to *Assets* based on *Asset Collection*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadSpecialAssets':
        matcher: 'isInCollection("some-collection")'

.. note:

  Instead of the *title* the *technical identifier (UUID)* of the asset collection can be used, too

Of course you can combine the three matchers like:

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadConfidentialPdfs':
        matcher: 'hasMediaType("application/pdf") && isTagged("confidential")'

Restrict read access to *Tags* based on *Tag label* or *id*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can match on the *label* of a Tag:

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadTagPrivilege':
      'Some.Package:ReadConfidentialTags':
        matcher: 'isLabeled("confidential")'

Or on its technical identifier (UUID):

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadTagPrivilege':
      'Some.Package:ReadConfidentialTags':
        matcher: 'hasId("3e8300a6-e5a7-4c3f-aae6-4d7ce35f2caa")'

Restrict read access to *Asset Collections* based on *Collection title* or *id*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can match on the *title* of an Asset Collection:

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege':
      'Some.Package:ReadSpecialAssetCollection':
        matcher: 'isTitled("some-collection")'

Or on its technical identifier (UUID):

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege':
      'Some.Package:ReadSpecialAssetCollection':
        matcher: 'hasId("7c1e8cbc-9205-406d-a384-f8e9440531ad")'

Complete Example:
-----------------

Given you have three "groups" and corresponding roles `Some.Package:Group1Editor`, `Some.Package:Group2Editor` and
`Some.Package:Group3Editor` as well as an administrative role ``Some.Package:Administrator`.

Now, if you have three "Asset Collections" named `group1`, `group2` and `group3` the following ``Policy.yaml`` would
restrict editors to only see collections and assets corresponding to their role:

.. code-block:: yaml

  privilegeTargets:

    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':

      'Some.Package:Group1.ReadAssets':
        matcher: 'isInCollection("group1")'
      'Some.Package:Group2.ReadAssets':
        matcher: 'isInCollection("group2")'
      'Some.Package:Group3.ReadAssets':
        matcher: 'isInCollection("group3")'

    'Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege':

      'Some.Package:Group1.ReadCollections':
        matcher: 'isTitled("group1")'
      'Some.Package:Group2.ReadCollections':
        matcher: 'isTitled("group2")'
      'Some.Package:Group3.ReadCollections':
        matcher: 'isTitled("group3")'

  roles:

    'Your.Package:Administrator':
      privileges:
        -
          privilegeTarget: 'Some.Package:Group1.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group1.ReadCollections'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group2.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group2.ReadCollections'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group3.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group3.ReadCollections'
          permission: GRANT

    'Your.Package:Group1Editor':
      privileges:
        -
          privilegeTarget: 'Some.Package:Group1.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group1.ReadCollections'
          permission: GRANT

    'Your.Package:Group2Editor':
      privileges:
        -
          privilegeTarget: 'Some.Package:Group2.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group2.ReadCollections'
          permission: GRANT

    'Your.Package:Group3Editor':
      privileges:
        -
          privilegeTarget: 'Some.Package:Group3.ReadAssets'
          permission: GRANT
        -
          privilegeTarget: 'Some.Package:Group3.ReadCollections'
          permission: GRANT
