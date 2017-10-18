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

Restrict read access to *Assets* based on *Asset Collection*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadSpecialAssets':
        matcher: 'isInCollection("some-collection")'

Of course you can combine the three matchers like:

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege':
      'Some.Package:ReadConfidentialPdfs':
        matcher: 'hasMediaType("application/pdf") && isTagged("confidential")'

Restrict read access to *Tags* based on *Tag label*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadTagPrivilege':
      'Some.Package:ReadConfidentialTags':
        matcher: 'isLabeled("confidential")'

Restrict read access to *Asset Collections* based on *Collection title*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

  privilegeTargets:
    'Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege':
      'Some.Package:ReadSpecialAssetCollection':
        matcher: 'isTitled("some-collection")'

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
