.. _TYPO3 Neos Command Reference:

TYPO3 Neos Command Reference
============================

.. note:

  This reference uses ``./flow`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow help

The following reference was automatically generated from code on 2015-04-17


Package *TYPO3.NEOS*
--------------------


``typo3.neos:domain:activate``
******************************

**Activate a domain record**



Arguments
^^^^^^^^^

``--host-pattern``
  The host pattern of the domain to activate







``typo3.neos:domain:add``
*************************

**Add a domain record**



Arguments
^^^^^^^^^

``--site-node-name``
  The nodeName of the site rootNode, e.g. "neostypo3org
``--host-pattern``
  The host pattern to match on, e.g. "neos.typo3.org







``typo3.neos:domain:deactivate``
********************************

**Deactivate a domain record**



Arguments
^^^^^^^^^

``--host-pattern``
  The host pattern of the domain to deactivate







``typo3.neos:domain:delete``
****************************

**Delete a domain record**



Arguments
^^^^^^^^^

``--host-pattern``
  The host pattern of the domain to remove







``typo3.neos:domain:list``
**************************

**Display a list of available domain records**





Options
^^^^^^^

``--host-pattern``
  An optional host pattern to search for





``typo3.neos:site:export``
**************************

**Export sites content**

This command exports all or one specific site with all its content into an XML format.

If the package key option is given, the site(s) will be exported to the given package in the default
location Resources/Private/Content/Sites.xml.

If the filename option is given, any resources will be exported to files in a folder named "Resources"
alongside the XML file.

If neither the filename nor the package key option are given, the XML will be printed to standard output and
assets will be embedded into the XML in base64 encoded form.



Options
^^^^^^^

``--site-node``
  the node name of the site to be exported; if none given will export all sites
``--tidy``
  Whether to export formatted XML
``--filename``
  relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources".
``--package-key``
  Package to store the XML file in. Any resource will be stored in a sub folder "Resources".





``typo3.neos:site:import``
**************************

**Import sites content**

This command allows for importing one or more sites or partial content from an XML source. The format must
be identical to that produced by the export command.

If a filename is specified, this command expects the corresponding file to contain the XML structure. The
filename php://stdin can be used to read from standard input.

If a package key is specified, this command expects a Sites.xml file to be located in the private resources
directory of the given package (Resources/Private/Content/Sites.xml).



Options
^^^^^^^

``--package-key``
  Package key specifying the package containing the sites content
``--filename``
  relative path and filename to the XML file containing the sites content





``typo3.neos:site:list``
************************

**Display a list of available sites**









``typo3.neos:site:prune``
*************************

**Remove all content and related data - for now. In the future we need some more sophisticated cleanup.**





Options
^^^^^^^

``--site-node-name``
  Name of a site root node to clear only content of this site.





``typo3.neos:user:activate``
****************************

**Activate a user which has access to the backend user interface.**



Arguments
^^^^^^^^^

``--username``
  The username of the user to be activated.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:addrole``
***************************

**Add a role to a user**

This command allows for adding a specific role to an existing user.
Currently supported roles: "TYPO3.Neos:Editor", "TYPO3.Neos:Administrator"

Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role ot be added to the use



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to user





``typo3.neos:user:create``
**************************

**Create a new user**

This command creates a new user which has access to the backend user interface.
It is recommended to user the email address as a username.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be created.
``--password``
  Password of the user to be created
``--first-name``
  First name of the user to be created
``--last-name``
  Last name of the user to be created



Options
^^^^^^^

``--roles``
  A comma separated list of roles to assign
``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:deactivate``
******************************

**Deactivate a user which has access to the backend user interface.**



Arguments
^^^^^^^^^

``--username``
  The username of the user to be deactivated.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:list``
************************

**List all users**





Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:remove``
**************************

**Remove a user which has access to the backend user interface.**



Arguments
^^^^^^^^^

``--username``
  The username of the user to be removed.



Options
^^^^^^^

``--confirmation``
  
``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:removerole``
******************************

**Remove a role from a user**



Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role ot be removed from the user



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:setpassword``
*******************************

**Set a new password for the given user**

This allows for setting a new password for an existing user account.

Arguments
^^^^^^^^^

``--username``
  Username of the account to modify
``--password``
  The new password



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:user:show``
************************

**Shows the given user**

This command shows some basic details about the given user. If such a user does not exist, this command
will exit with a non-zero status code.

Arguments
^^^^^^^^^

``--username``
  The username of the user to show.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use





``typo3.neos:workspace:discard``
********************************

**Discard changes in workspace**

This command discards all modified, created or deleted nodes in the specified workspace.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace, for example "user-john



Options
^^^^^^^

``--verbose``
  If enabled, information about individual nodes will be displayed
``--dry-run``
  If set, only displays which nodes would be discarded, no real changes are committed





``typo3.neos:workspace:discardall``
***********************************

**Discard changes in workspace &lt;b&gt;(DEPRECATED)&lt;/b&gt;**

This command discards all modified, created or deleted nodes in the specified workspace.

Arguments
^^^^^^^^^

``--workspace-name``
  Name of the workspace, for example "user-john



Options
^^^^^^^

``--verbose``
  If enabled, information about individual nodes will be displayed



Related commands
^^^^^^^^^^^^^^^^

``typo3.neos:workspace:discard``
  Discard changes in workspace



``typo3.neos:workspace:list``
*****************************

**Display a list of existing workspaces**









``typo3.neos:workspace:publish``
********************************

**Publish changes of a workspace**

This command publishes all modified, created or deleted nodes in the specified workspace to the live workspace.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace containing the changes to publish, for example "user-john



Options
^^^^^^^

``--verbose``
  If enabled, some information about individual nodes will be displayed
``--dry-run``
  If set, only displays which nodes would be published, no real changes are committed





``typo3.neos:workspace:publishall``
***********************************

**Publish changes of a workspace &lt;b&gt;(DEPRECATED)&lt;/b&gt;**

This command publishes all modified, created or deleted nodes in the specified workspace to the live workspace.

Arguments
^^^^^^^^^

``--workspace-name``
  Name of the workspace, for example "user-john



Options
^^^^^^^

``--verbose``
  If enabled, information about individual nodes will be displayed



Related commands
^^^^^^^^^^^^^^^^

``typo3.neos:workspace:publish``
  Publish changes of a workspace



