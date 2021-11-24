.. _`Neos Command Reference`:

Neos Command Reference
======================

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

The following reference was automatically generated from code on 2017-05-11


.. _`Neos Command Reference: NEOS.CONTENTREPOSITORY`:

Package *NEOS.CONTENTREPOSITORY*
--------------------------------


.. _`Neos Command Reference: NEOS.CONTENTREPOSITORY neos.contentrepository:node:repair`:

``neos.contentrepository:node:repair``
**************************************

**Repair inconsistent nodes**

This command analyzes and repairs the node tree structure and individual nodes
based on the current node type configuration.

It is possible to execute only one or more specific checks by providing the **--skip**
or **--only** option. See the full description of checks further below for possible check
identifiers.

The following checks will be performed:

*Remove abstract and undefined node types*
removeAbstractAndUndefinedNodes

Will remove all nodes that has an abstract or undefined node type.

*Remove orphan (parentless) nodes*
removeOrphanNodes

Will remove all child nodes that do not have a connection to the root node.

*Remove disallowed child nodes*
removeDisallowedChildNodes

Will remove all child nodes that are disallowed according to the node type's auto-create
configuration and constraints.

*Remove undefined node properties*
removeUndefinedProperties

Will remove all undefined properties according to the node type configuration.

*Remove broken object references*
removeBrokenEntityReferences

Detects and removes references from nodes to entities which don't exist anymore (for
example Image nodes referencing ImageVariant objects which are gone for some reason).

*Remove nodes with invalid dimensions*
removeNodesWithInvalidDimensions

Will check for and optionally remove nodes which have dimension values not matching
the current content dimension configuration.

*Remove nodes with invalid workspace*
removeNodesWithInvalidWorkspace

Will check for and optionally remove nodes which belong to a workspace which no longer
exists..

*Repair inconsistent node identifiers*
fixNodesWithInconsistentIdentifier

Will check for and optionally repair node identifiers which are out of sync with their
corresponding nodes in a live workspace.

*Missing child nodes*
createMissingChildNodes

For all nodes (or only those which match the --node-type filter specified with this
command) which currently don't have child nodes as configured by the node type's
configuration new child nodes will be created.

*Reorder child nodes*
reorderChildNodes

For all nodes (or only those which match the --node-type filter specified with this
command) which have configured child nodes, those child nodes are reordered according to the
position from the parents NodeType configuration.
*Missing default properties*
addMissingDefaultValues

For all nodes (or only those which match the --node-type filter specified with this
command) which currently don\t have a property that have a default value configuration
the default value for that property will be set.

*Repair nodes with missing shadow nodes*
repairShadowNodes

This will reconstruct missing shadow nodes in case something went wrong in creating
or publishing them. This must be used on a workspace other than live.

It searches for nodes which have a corresponding node in one of the base workspaces,
have different node paths, but don't have a corresponding shadow node with a "movedto"
value.

*Generate missing URI path segments*
generateUriPathSegments

Generates URI path segment properties for all document nodes which don't have a path
segment set yet.

*Remove content dimensions from / and /sites*
removeContentDimensionsFromRootAndSitesNode

Removes content dimensions from the root and sites nodes


**Examples:**

``./flow node:repair``

``./flow node:repair --node-type Neos.NodeTypes:Page``

``./flow node:repair --workspace user-robert --only removeOrphanNodes,removeNodesWithInvalidDimensions``

``./flow node:repair --skip removeUndefinedProperties``



Options
^^^^^^^

``--node-type``
  Node type name, if empty update all declared node types
``--workspace``
  Workspace name, default is 'live'
``--dry-run``
  Don't do anything, but report actions
``--cleanup``
  If FALSE, cleanup tasks are skipped
``--skip``
  Skip the given check or checks (comma separated)
``--only``
  Only execute the given check or checks (comma separated)





.. _`Neos Command Reference: NEOS.FLOW`:

Package *NEOS.FLOW*
-------------------


.. _`Neos Command Reference: NEOS.FLOW neos.flow:cache:flush`:

``neos.flow:cache:flush``
*************************

**Flush all caches**

The flush command flushes all caches (including code caches) which have been
registered with Flow's Cache Manager. It also removes any session data.

If fatal errors caused by a package prevent the compile time bootstrap
from running, the removal of any temporary data can be forced by specifying
the option **--force**.

This command does not remove the precompiled data provided by frozen
packages unless the **--force** option is used.



Options
^^^^^^^

``--force``
  Force flushing of any temporary data



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:warmup``
  Warm up caches
``neos.flow:package:freeze``
  Freeze a package
``neos.flow:package:refreeze``
  Refreeze a package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:cache:flushone`:

``neos.flow:cache:flushone``
****************************

**Flushes a particular cache by its identifier**

Given a cache identifier, this flushes just that one cache. To find
the cache identifiers, you can use the configuration:show command with
the type set to "Caches".

Note that this does not have a force-flush option since it's not
meant to remove temporary code data, resulting into a broken state if
code files lack.

Arguments
^^^^^^^^^

``--identifier``
  Cache identifier to flush cache for





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:flush``
  Flush all caches
``neos.flow:configuration:show``
  Show the active configuration settings



.. _`Neos Command Reference: NEOS.FLOW neos.flow:cache:warmup`:

``neos.flow:cache:warmup``
**************************

**Warm up caches**

The warm up caches command initializes and fills – as far as possible – all
registered caches to get a snappier response on the first following request.
Apart from caches, other parts of the application may hook into this command
and execute tasks which take further steps for preparing the app for the big
rush.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:flush``
  Flush all caches



.. _`Neos Command Reference: NEOS.FLOW neos.flow:configuration:generateschema`:

``neos.flow:configuration:generateschema``
******************************************

**Generate a schema for the given configuration or YAML file.**

./flow configuration:generateschema --type Settings --path Neos.Flow.persistence

The schema will be output to standard output.



Options
^^^^^^^

``--type``
  Configuration type to create a schema for
``--path``
  path to the subconfiguration separated by "." like "Neos.Flow
``--yaml``
  YAML file to create a schema for





.. _`Neos Command Reference: NEOS.FLOW neos.flow:configuration:listtypes`:

``neos.flow:configuration:listtypes``
*************************************

**List registered configuration types**









.. _`Neos Command Reference: NEOS.FLOW neos.flow:configuration:show`:

``neos.flow:configuration:show``
********************************

**Show the active configuration settings**

The command shows the configuration of the current context as it is used by Flow itself.
You can specify the configuration type and path if you want to show parts of the configuration.

./flow configuration:show --type Settings --path Neos.Flow.persistence



Options
^^^^^^^

``--type``
  Configuration type to show
``--path``
  path to subconfiguration separated by "." like "Neos.Flow





.. _`Neos Command Reference: NEOS.FLOW neos.flow:configuration:validate`:

``neos.flow:configuration:validate``
************************************

**Validate the given configuration**

**Validate all configuration**
./flow configuration:validate

**Validate configuration at a certain subtype**
./flow configuration:validate --type Settings --path Neos.Flow.persistence

You can retrieve the available configuration types with:
./flow configuration:listtypes



Options
^^^^^^^

``--type``
  Configuration type to validate
``--path``
  path to the subconfiguration separated by "." like "Neos.Flow
``--verbose``
  if TRUE, output more verbose information on the schema files which were used





.. _`Neos Command Reference: NEOS.FLOW neos.flow:core:migrate`:

``neos.flow:core:migrate``
**************************

**Migrate source files as needed**

This will apply pending code migrations defined in packages to the
specified package.

For every migration that has been run, it will create a commit in
the package. This allows for easy inspection, rollback and use of
the fixed code.
If the affected package contains local changes or is not part of
a git repository, the migration will be skipped. With the --force
flag this behavior can be changed, but changes will only be committed
if the working copy was clean before applying the migration.

Arguments
^^^^^^^^^

``--package``
  The key of the package to migrate



Options
^^^^^^^

``--status``
  Show the migration status, do not run migrations
``--packages-path``
  If set, use the given path as base when looking for packages
``--version``
  If set, execute only the migration with the given version (e.g. "20150119114100")
``--verbose``
  If set, notes and skipped migrations will be rendered
``--force``
  By default packages that are not under version control or contain local changes are skipped. With this flag set changes are applied anyways (changes are not committed if there are local changes though)



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Neos Command Reference: NEOS.FLOW neos.flow:core:setfilepermissions`:

``neos.flow:core:setfilepermissions``
*************************************

**Adjust file permissions for CLI and web server access**

This command adjusts the file permissions of the whole Flow application to
the given command line user and webserver user / group.

Arguments
^^^^^^^^^

``--commandline-user``
  User name of the command line user, for example "john
``--webserver-user``
  User name of the webserver, for example "www-data
``--webserver-group``
  Group name of the webserver, for example "www-data







.. _`Neos Command Reference: NEOS.FLOW neos.flow:core:shell`:

``neos.flow:core:shell``
************************

**Run the interactive Shell**

The shell command runs Flow's interactive shell. This shell allows for
entering commands like through the regular command line interface but
additionally supports autocompletion and a user-based command history.







.. _`Neos Command Reference: NEOS.FLOW neos.flow:database:setcharset`:

``neos.flow:database:setcharset``
*********************************

**Convert the database schema to use the given character set and collation (defaults to utf8mb4 and utf8mb4_unicode_ci).**

This command can be used to convert the database configured in the Flow settings to the utf8mb4 character
set and the utf8mb4_unicode_ci collation (by default, a custom collation can be given). It will only
work when using the pdo_mysql driver.

**Make a backup** before using it, to be on the safe side. If you want to inspect the statements used
for conversion, you can use the $output parameter to write them into a file. This file can be used to do
the conversion manually.

For background information on this, see:

- http://stackoverflow.com/questions/766809/
- http://dev.mysql.com/doc/refman/5.5/en/alter-table.html

The main purpose of this is to fix setups that were created with Flow 2.3.x or earlier and whose
database server did not have a default collation of utf8mb4_unicode_ci. In those cases, the tables will
have a collation that does not match the default collation of later Flow versions, potentially leading
to problems when creating foreign key constraints (among others, potentially).

If you have special needs regarding the charset and collation, you *can* override the defaults with
different ones. One thing this might be useful for is when switching to the utf8mb4mb4 character set, see:

- https://mathiasbynens.be/notes/mysql-utf8mb4
- https://florian.ec/articles/mysql-doctrine-utf8/

Note: This command **is not a general purpose conversion tool**. It will specifically not fix cases
of actual utf8mb4 stored in latin1 columns. For this a conversion to BLOB followed by a conversion to the
proper type, charset and collation is needed instead.



Options
^^^^^^^

``--character-set``
  Character set, defaults to utf8mb4
``--collation``
  Collation to use, defaults to utf8mb4_unicode_ci
``--output``
  A file to write SQL to, instead of executing it
``--verbose``
  If set, the statements will be shown as they are executed





.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:create`:

``neos.flow:doctrine:create``
*****************************

**Create the database schema**

Creates a new database schema based on the current mapping information.

It expects the database to be empty, if tables that are to be created already
exist, this will lead to errors.



Options
^^^^^^^

``--output``
  A file to write SQL to, instead of executing it



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:update``
  Update the database schema
``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:dql`:

``neos.flow:doctrine:dql``
**************************

**Run arbitrary DQL and display results**

Any DQL queries passed after the parameters will be executed, the results will be output:

doctrine:dql --limit 10 'SELECT a FROM Neos\Flow\Security\Account a'



Options
^^^^^^^

``--depth``
  How many levels deep the result should be dumped
``--hydration-mode``
  One of: object, array, scalar, single-scalar, simpleobject
``--offset``
  Offset the result by this number
``--limit``
  Limit the result to this number





.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:entitystatus`:

``neos.flow:doctrine:entitystatus``
***********************************

**Show the current status of entities and mappings**

Shows basic information about which entities exist and possibly if their
mapping information contains errors or not.

To run a full validation, use the validate command.



Options
^^^^^^^

``--dump-mapping-data``
  If set, the mapping data will be output
``--entity-class-name``
  If given, the mapping data for just this class will be output



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:validate``
  Validate the class/table mappings



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:migrate`:

``neos.flow:doctrine:migrate``
******************************

**Migrate the database schema**

Adjusts the database structure by applying the pending
migrations provided by currently active packages.



Options
^^^^^^^

``--version``
  The version to migrate to
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not
``--quiet``
  If set, only the executed migration versions will be output, one per line



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:migrationexecute`:

``neos.flow:doctrine:migrationexecute``
***************************************

**Execute a single migration**

Manually runs a single migration in the given direction.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--direction``
  Whether to execute the migration up (default) or down
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:migrationgenerate`:

``neos.flow:doctrine:migrationgenerate``
****************************************

**Generate a new migration**

If $diffAgainstCurrent is TRUE (the default), it generates a migration file
with the diff between current DB structure and the found mapping metadata.

Otherwise an empty migration skeleton is generated.

Only includes tables/sequences matching the $filterExpression regexp when
diffing models and existing schema. Include delimiters in the expression!
The use of

--filter-expression '/^acme_com/'

would only create a migration touching tables starting with "acme_com".

Note: A filter-expression will overrule any filter configured through the
Neos.Flow.persistence.doctrine.migrations.ignoredTables setting



Options
^^^^^^^

``--diff-against-current``
  Whether to base the migration on the current schema structure
``--filter-expression``
  Only include tables/sequences matching the filter expression regexp



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:migrationstatus`:

``neos.flow:doctrine:migrationstatus``
**************************************

**Show the current migration status**

Displays the migration configuration as well as the number of
available, executed and pending migrations.



Options
^^^^^^^

``--show-migrations``
  Output a list of all migrations and their status
``--show-descriptions``
  Show descriptions for the migrations (enables versions display)



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:migrationversion`:

``neos.flow:doctrine:migrationversion``
***************************************

**Mark/unmark migrations as migrated**

If *all* is given as version, all available migrations are marked
as requested.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--add``
  The migration to mark as migrated
``--delete``
  The migration to mark as not migrated



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:update`:

``neos.flow:doctrine:update``
*****************************

**Update the database schema**

Updates the database schema without using existing migrations.

It will not drop foreign keys, sequences and tables, unless *--unsafe-mode* is set.



Options
^^^^^^^

``--unsafe-mode``
  If set, foreign keys, sequences and tables can potentially be dropped.
``--output``
  A file to write SQL to, instead of executing the update directly



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:create``
  Create the database schema
``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Neos Command Reference: NEOS.FLOW neos.flow:doctrine:validate`:

``neos.flow:doctrine:validate``
*******************************

**Validate the class/table mappings**

Checks if the current class model schema is valid. Any inconsistencies
in the relations between models (for example caused by wrong or
missing annotations) will be reported.

Note that this does not check the table structure in the database in
any way.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:entitystatus``
  Show the current status of entities and mappings



.. _`Neos Command Reference: NEOS.FLOW neos.flow:help:help`:

``neos.flow:help:help``
***********************

**Display help for a command**

The help command displays help for a given command:
./flow help <commandIdentifier>



Options
^^^^^^^

``--command-identifier``
  Identifier of a command for more details





.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:activate`:

``neos.flow:package:activate``
******************************

**Activate an available package**

This command activates an existing, but currently inactive package.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:deactivate``
  Deactivate a package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:create`:

``neos.flow:package:create``
****************************

**Create a new package**

This command creates a new package which contains only the mandatory
directories and files.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create



Options
^^^^^^^

``--package-type``
  The package type of the package to create



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:package``
  Kickstart a new package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:deactivate`:

``neos.flow:package:deactivate``
********************************

**Deactivate a package**

This command deactivates a currently active package.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:activate``
  Activate an available package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:delete`:

``neos.flow:package:delete``
****************************

**Delete an existing package**

This command deletes an existing package identified by the package key.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create







.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:freeze`:

``neos.flow:package:freeze``
****************************

**Freeze a package**

This function marks a package as **frozen** in order to improve performance
in a development context. While a package is frozen, any modification of files
within that package won't be tracked and can lead to unexpected behavior.

File monitoring won't consider the given package. Further more, reflection
data for classes contained in the package is cached persistently and loaded
directly on the first request after caches have been flushed. The precompiled
reflection data is stored in the **Configuration** directory of the
respective package.

By specifying **all** as a package key, all currently frozen packages are
frozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to freeze



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:unfreeze``
  Unfreeze a package
``neos.flow:package:refreeze``
  Refreeze a package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:list`:

``neos.flow:package:list``
**************************

**List available packages**

Lists all locally available packages. Displays the package key, version and
package title and its state – active or inactive.



Options
^^^^^^^

``--loading-order``
  The returned packages are ordered by their loading order.



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:activate``
  Activate an available package
``neos.flow:package:deactivate``
  Deactivate a package



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:refreeze`:

``neos.flow:package:refreeze``
******************************

**Refreeze a package**

Refreezes a currently frozen package: all precompiled information is removed
and file monitoring will consider the package exactly once, on the next
request. After that request, the package remains frozen again, just with the
updated data.

By specifying **all** as a package key, all currently frozen packages are
refrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to refreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:freeze``
  Freeze a package
``neos.flow:cache:flush``
  Flush all caches



.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:rescan`:

``neos.flow:package:rescan``
****************************

**Rescan package availability and recreates the PackageStates configuration.**









.. _`Neos Command Reference: NEOS.FLOW neos.flow:package:unfreeze`:

``neos.flow:package:unfreeze``
******************************

**Unfreeze a package**

Unfreezes a previously frozen package. On the next request, this package will
be considered again by the file monitoring and related services – if they are
enabled in the current context.

By specifying **all** as a package key, all currently frozen packages are
unfrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to unfreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:freeze``
  Freeze a package
``neos.flow:cache:flush``
  Flush all caches



.. _`Neos Command Reference: NEOS.FLOW neos.flow:resource:clean`:

``neos.flow:resource:clean``
****************************

**Clean up resource registry**

This command checks the resource registry (that is the database tables) for orphaned resource objects which don't
seem to have any corresponding data anymore (for example: the file in Data/Persistent/Resources has been deleted
without removing the related PersistentResource object).

If the Neos.Media package is active, this command will also detect any assets referring to broken resources
and will remove the respective Asset object from the database when the broken resource is removed.

This command will ask you interactively what to do before deleting anything.







.. _`Neos Command Reference: NEOS.FLOW neos.flow:resource:copy`:

``neos.flow:resource:copy``
***************************

**Copy resources**

This command copies all resources from one collection to another storage identified by name.
The target storage must be empty and must not be identical to the current storage of the collection.

This command merely copies the binary data from one storage to another, it does not change the related
PersistentResource objects in the database in any way. Since the PersistentResource objects in the database refer to a
collection name, you can use this command for migrating from one storage to another my configuring
the new storage with the name of the old storage collection after the resources have been copied.

Arguments
^^^^^^^^^

``--source-collection``
  The name of the collection you want to copy the assets from
``--target-collection``
  The name of the collection you want to copy the assets to



Options
^^^^^^^

``--publish``
  If enabled, the target collection will be published after the resources have been copied





.. _`Neos Command Reference: NEOS.FLOW neos.flow:resource:publish`:

``neos.flow:resource:publish``
******************************

**Publish resources**

This command publishes the resources of the given or - if none was specified, all - resource collections
to their respective configured publishing targets.



Options
^^^^^^^

``--collection``
  If specified, only resources of this collection are published. Example: 'persistent'





.. _`Neos Command Reference: NEOS.FLOW neos.flow:routing:getpath`:

``neos.flow:routing:getpath``
*****************************

**Generate a route path**

This command takes package, controller and action and displays the
generated route path and the selected route:

./flow routing:getPath --format json Acme.Demo\\Sub\\Package

Arguments
^^^^^^^^^

``--package``
  Package key and subpackage, subpackage parts are separated with backslashes



Options
^^^^^^^

``--controller``
  Controller name, default is 'Standard'
``--action``
  Action name, default is 'index'
``--format``
  Requested Format name default is 'html'





.. _`Neos Command Reference: NEOS.FLOW neos.flow:routing:list`:

``neos.flow:routing:list``
**************************

**List the known routes**

This command displays a list of all currently registered routes.







.. _`Neos Command Reference: NEOS.FLOW neos.flow:routing:routepath`:

``neos.flow:routing:routepath``
*******************************

**Route the given route path**

This command takes a given path and displays the detected route and
the selected package, controller and action.

Arguments
^^^^^^^^^

``--path``
  The route path to resolve



Options
^^^^^^^

``--method``
  The request method (GET, POST, PUT, DELETE, ...) to simulate





.. _`Neos Command Reference: NEOS.FLOW neos.flow:routing:show`:

``neos.flow:routing:show``
**************************

**Show information for a route**

This command displays the configuration of a route specified by index number.

Arguments
^^^^^^^^^

``--index``
  The index of the route as given by routing:list







.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:generatekeypair`:

``neos.flow:security:generatekeypair``
**************************************

**Generate a public/private key pair and add it to the RSAWalletService**





Options
^^^^^^^

``--used-for-passwords``
  If the private key should be used for passwords



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importprivatekey``
  Import a private key



.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:importprivatekey`:

``neos.flow:security:importprivatekey``
***************************************

**Import a private key**

Read a PEM formatted private key from stdin and import it into the
RSAWalletService. The public key will be automatically extracted and stored
together with the private key as a key pair.

You can generate the same fingerprint returned from this using these commands:

ssh-keygen -yf my-key.pem > my-key.pub
ssh-keygen -lf my-key.pub

To create a private key to import using this method, you can use:

ssh-keygen -t rsa -f my-key
./flow security:importprivatekey < my-key

Again, the fingerprint can also be generated using:

ssh-keygen -lf my-key.pub



Options
^^^^^^^

``--used-for-passwords``
  If the private key should be used for passwords



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importpublickey``
  Import a public key
``neos.flow:security:generatekeypair``
  Generate a public/private key pair and add it to the RSAWalletService



.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:importpublickey`:

``neos.flow:security:importpublickey``
**************************************

**Import a public key**

Read a PEM formatted public key from stdin and import it into the
RSAWalletService.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importprivatekey``
  Import a private key



.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:showeffectivepolicy`:

``neos.flow:security:showeffectivepolicy``
******************************************

**Shows a list of all defined privilege targets and the effective permissions**



Arguments
^^^^^^^^^

``--privilege-type``
  The privilege type ("entity", "method" or the FQN of a class implementing PrivilegeInterface)



Options
^^^^^^^

``--roles``
  A comma separated list of role identifiers. Shows policy for an unauthenticated user when left empty.





.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:showmethodsforprivilegetarget`:

``neos.flow:security:showmethodsforprivilegetarget``
****************************************************

**Shows the methods represented by the given security privilege target**

If the privilege target has parameters those can be specified separated by a colon
for example "parameter1:value1" "parameter2:value2".
But be aware that this only works for parameters that have been specified in the policy

Arguments
^^^^^^^^^

``--privilege-target``
  The name of the privilegeTarget as stated in the policy







.. _`Neos Command Reference: NEOS.FLOW neos.flow:security:showunprotectedactions`:

``neos.flow:security:showunprotectedactions``
*********************************************

**Lists all public controller actions not covered by the active security policy**









.. _`Neos Command Reference: NEOS.FLOW neos.flow:server:run`:

``neos.flow:server:run``
************************

**Run a standalone development server**

Starts an embedded server, see http://php.net/manual/en/features.commandline.webserver.php
Note: This requires PHP 5.4+

To change the context Flow will run in, you can set the **FLOW_CONTEXT** environment variable:
*export FLOW_CONTEXT=Development && ./flow server:run*



Options
^^^^^^^

``--host``
  The host name or IP address for the server to listen on
``--port``
  The server port to listen on





.. _`Neos Command Reference: NEOS.FLOW neos.flow:typeconverter:list`:

``neos.flow:typeconverter:list``
********************************

**Lists all currently active and registered type converters**

All active converters are listed with ordered by priority and grouped by
source type first and target type second.



Options
^^^^^^^

``--source``
  Filter by source
``--target``
  Filter by target type





.. _`Neos Command Reference: NEOS.FLUIDADAPTOR`:

Package *NEOS.FLUIDADAPTOR*
---------------------------


.. _`Neos Command Reference: NEOS.FLUIDADAPTOR neos.fluidadaptor:documentation:generatexsd`:

``neos.fluidadaptor:documentation:generatexsd``
***********************************************

**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>

Arguments
^^^^^^^^^

``--php-namespace``
  Namespace of the Fluid ViewHelpers without leading backslash (for example 'Neos\FluidAdaptor\ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
^^^^^^^

``--xsd-namespace``
  Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
``--target-file``
  File path and name of the generated XSD schema. If not specified the schema will be output to standard output.





.. _`Neos Command Reference: NEOS.KICKSTARTER`:

Package *NEOS.KICKSTARTER*
--------------------------


.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:actioncontroller`:

``neos.kickstarter:kickstart:actioncontroller``
***********************************************

**Kickstart a new action controller**

Generates an Action Controller with the given name in the specified package.
In its default mode it will create just the controller containing a sample
indexAction.

By specifying the --generate-actions flag, this command will also create a
set of actions. If no model or repository exists which matches the
controller name (for example "CoffeeRepository" for "CoffeeController"),
an error will be shown.

Likewise the command exits with an error if the specified package does not
exist. By using the --generate-related flag, a missing package, model or
repository can be created alongside, avoiding such an error.

By specifying the --generate-templates flag, this command will also create
matching Fluid templates for the actions created. This option can only be
used in combination with --generate-actions.

The default behavior is to not overwrite any existing code. This can be
overridden by specifying the --force flag.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
``--controller-name``
  The name for the new controller. This may also be a comma separated list of controller names.



Options
^^^^^^^

``--generate-actions``
  Also generate index, show, new, create, edit, update and delete actions.
``--generate-templates``
  Also generate the templates for each action.
``--generate-related``
  Also create the mentioned package, related model and repository if neccessary.
``--force``
  Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:commandcontroller``
  Kickstart a new command controller



.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:commandcontroller`:

``neos.kickstarter:kickstart:commandcontroller``
************************************************

**Kickstart a new command controller**

Creates a new command controller with the given name in the specified
package. The generated controller class already contains an example command.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the new controller
``--controller-name``
  The name for the new controller. This may also be a comma separated list of controller names.



Options
^^^^^^^

``--force``
  Overwrite any existing controller.



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:actioncontroller``
  Kickstart a new action controller



.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:documentation`:

``neos.kickstarter:kickstart:documentation``
********************************************

**Kickstart documentation**

Generates a documentation skeleton for the given package.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the documentation







.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:model`:

``neos.kickstarter:kickstart:model``
************************************

**Kickstart a new domain model**

This command generates a new domain model class. The fields are specified as
a variable list of arguments with field name and type separated by a colon
(for example "title:string" "size:int" "type:MyType").

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the domain model
``--model-name``
  The name of the new domain model class



Options
^^^^^^^

``--force``
  Overwrite any existing model.



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:repository``
  Kickstart a new domain repository



.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:package`:

``neos.kickstarter:kickstart:package``
**************************************

**Kickstart a new package**

Creates a new package and creates a standard Action Controller and a sample
template for its Index Action.

For creating a new package without sample code use the package:create command.

Arguments
^^^^^^^^^

``--package-key``
  The package key, for example "MyCompany.MyPackageName





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow:package:create``
  *Command not available*



.. _`Neos Command Reference: NEOS.KICKSTARTER neos.kickstarter:kickstart:repository`:

``neos.kickstarter:kickstart:repository``
*****************************************

**Kickstart a new domain repository**

This command generates a new domain repository class for the given model name.

Arguments
^^^^^^^^^

``--package-key``
  The package key
``--model-name``
  The name of the domain model class



Options
^^^^^^^

``--force``
  Overwrite any existing repository.



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:model``
  Kickstart a new domain model



.. _`Neos Command Reference: NEOS.MEDIA`:

Package *NEOS.MEDIA*
--------------------


.. _`Neos Command Reference: NEOS.MEDIA neos.media:media:clearthumbnails`:

``neos.media:media:clearthumbnails``
************************************

**Remove thumbnails**

Removes all thumbnail objects and their resources. Optional ``preset`` parameter to only remove thumbnails
matching a specific thumbnail preset configuration.



Options
^^^^^^^

``--preset``
  Preset name, if provided only thumbnails matching that preset are cleared





.. _`Neos Command Reference: NEOS.MEDIA neos.media:media:createthumbnails`:

``neos.media:media:createthumbnails``
*************************************

**Create thumbnails**

Creates thumbnail images based on the configured thumbnail presets. Optional ``preset`` parameter to only create
thumbnails for a specific thumbnail preset configuration.

Additionally accepts a ``async`` parameter determining if the created thumbnails are generated when created.



Options
^^^^^^^

``--preset``
  Preset name, if not provided thumbnails are created for all presets
``--async``
  Asynchronous generation, if not provided the setting ``Neos.Media.asyncThumbnails`` is used





.. _`Neos Command Reference: NEOS.MEDIA neos.media:media:importresources`:

``neos.media:media:importresources``
************************************

**Import resources to asset management**

This command detects Flow "PersistentResource"s which are not yet available as "Asset" objects and thus don't appear
in the asset management. The type of the imported asset is determined by the file extension provided by the
PersistentResource.



Options
^^^^^^^

``--simulate``
  If set, this command will only tell what it would do instead of doing it right away





.. _`Neos Command Reference: NEOS.MEDIA neos.media:media:renderthumbnails`:

``neos.media:media:renderthumbnails``
*************************************

**Render ungenerated thumbnails**

Loops over ungenerated thumbnails and renders them. Optional ``limit`` parameter to limit the amount of
thumbnails to be rendered to avoid memory exhaustion.



Options
^^^^^^^

``--limit``
  Limit the amount of thumbnails to be rendered to avoid memory exhaustion





.. _`Neos Command Reference: NEOS.NEOS`:

Package *NEOS.NEOS*
-------------------


.. _`Neos Command Reference: NEOS.NEOS neos.neos:domain:activate`:

``neos.neos:domain:activate``
*****************************

**Activate a domain record by hostname**



Arguments
^^^^^^^^^

``--hostname``
  The hostname to activate







.. _`Neos Command Reference: NEOS.NEOS neos.neos:domain:add`:

``neos.neos:domain:add``
************************

**Add a domain record**



Arguments
^^^^^^^^^

``--site-node-name``
  The nodeName of the site rootNode, e.g. "neostypo3org
``--hostname``
  The hostname to match on, e.g. "flow.neos.io



Options
^^^^^^^

``--scheme``
  The scheme for linking (http/https)
``--port``
  The port for linking (0-49151)





.. _`Neos Command Reference: NEOS.NEOS neos.neos:domain:deactivate`:

``neos.neos:domain:deactivate``
*******************************

**Deactivate a domain record by hostname**



Arguments
^^^^^^^^^

``--hostname``
  The hostname to deactivate







.. _`Neos Command Reference: NEOS.NEOS neos.neos:domain:delete`:

``neos.neos:domain:delete``
***************************

**Delete a domain record by hostname**



Arguments
^^^^^^^^^

``--hostname``
  The hostname to remove







.. _`Neos Command Reference: NEOS.NEOS neos.neos:domain:list`:

``neos.neos:domain:list``
*************************

**Display a list of available domain records**





Options
^^^^^^^

``--hostname``
  An optional hostname to search for





.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:activate`:

``neos.neos:site:activate``
***************************

**Activate a site**

This command activates the specified site.

Arguments
^^^^^^^^^

``--site-node``
  The node name of the site to activate







.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:create`:

``neos.neos:site:create``
*************************

**Create a new site**

This command allows to create a blank site with just a single empty document in the default dimension.
The name of the site, the packageKey must be specified.

If no ``nodeType`` option is specified the command will use `Neos.NodeTypes:Page` as fallback. The node type
must already exists and have the superType ``Neos.Neos:Document``.

If no ``nodeName` option is specified the command will create a unique node-name from the name of the site.
If a node name is given it has to be unique for the setup.

If the flag ``activate` is set to false new site will not be activated.

Arguments
^^^^^^^^^

``--name``
  The name of the site
``--package-key``
  The site package



Options
^^^^^^^

``--node-type``
  The node type to use for the site node. (Default = Neos.NodeTypes:Page)
``--node-name``
  The name of the site node. If no nodeName is given it will be determined from the siteName.
``--inactive``
  The new site is not activated immediately (default = false).





.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:deactivate`:

``neos.neos:site:deactivate``
*****************************

**Deactivate a site**

This command deactivates the specified site.

Arguments
^^^^^^^^^

``--site-node``
  The node name of the site to deactivate







.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:export`:

``neos.neos:site:export``
*************************

**Export sites content (e.g. site:export --package-key &quot;Neos.Demo&quot;)**

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
  Whether to export formatted XML. This is defaults to true
``--filename``
  relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources".
``--package-key``
  Package to store the XML file in. Any resource will be stored in a sub folder "Resources".
``--node-type-filter``
  Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text")





.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:import`:

``neos.neos:site:import``
*************************

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





.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:list`:

``neos.neos:site:list``
***********************

**List available sites**









.. _`Neos Command Reference: NEOS.NEOS neos.neos:site:prune`:

``neos.neos:site:prune``
************************

**Remove all content and related data - for now. In the future we need some more sophisticated cleanup.**





Options
^^^^^^^

``--site-node``
  Name of a site root node to clear only content of this site.





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:activate`:

``neos.neos:user:activate``
***************************

**Activate a user**

This command reactivates possibly expired accounts for the given user.

If an authentication provider is specified, this command will look for an account with the given username related
to the given provider. Still, this command will activate **all** accounts of a user, once such a user has been
found.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be activated.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use for finding the user. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:addrole`:

``neos.neos:user:addrole``
**************************

**Add a role to a user**

This command allows for adding a specific role to an existing user.

Roles can optionally be specified as a comma separated list. For all roles provided by Neos, the role
namespace "Neos.Neos:" can be omitted.

If an authentication provider was specified, the user will be determined by an account identified by "username"
related to the given provider. However, once a user has been found, the new role will be added to **all**
existing accounts related to that user, regardless of its authentication provider.

Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role to be added to the user, for example "Neos.Neos:Administrator" or just "Administrator



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:create`:

``neos.neos:user:create``
*************************

**Create a new user**

This command creates a new user which has access to the backend user interface.

More specifically, this command will create a new user and a new account at the same time. The created account
is, by default, a Neos backend account using the the "Neos.Neos:Backend" for authentication. The given username
will be used as an account identifier for that new account.

If an authentication provider name is specified, the new account will be created for that provider instead.

Roles for the new user can optionally be specified as a comma separated list. For all roles provided by
Neos, the role namespace "Neos.Neos:" can be omitted.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be created, used as an account identifier for the newly created account
``--password``
  Password of the user to be created
``--first-name``
  First name of the user to be created
``--last-name``
  Last name of the user to be created



Options
^^^^^^^

``--roles``
  A comma separated list of roles to assign. Examples: "Editor, Acme.Foo:Reviewer
``--authentication-provider``
  Name of the authentication provider to use for the new account. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:deactivate`:

``neos.neos:user:deactivate``
*****************************

**Deactivate a user**

This command deactivates a user by flagging all of its accounts as expired.

If an authentication provider is specified, this command will look for an account with the given username related
to the given provider. Still, this command will deactivate **all** accounts of a user, once such a user has been
found.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be deactivated.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use for finding the user. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:delete`:

``neos.neos:user:delete``
*************************

**Delete a user**

This command deletes an existing Neos user. All content and data directly related to this user, including but
not limited to draft workspace contents, will be removed as well.

All accounts owned by the given user will be deleted.

If an authentication provider is specified, this command will look for an account with the given username related
to the given provider. Specifying an authentication provider does **not** mean that only the account for that
provider is deleted! If a user was found by the combination of username and authentication provider, **all**
related accounts will be deleted.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be removed



Options
^^^^^^^

``--assume-yes``
  Assume "yes" as the answer to the confirmation dialog
``--authentication-provider``
  Name of the authentication provider to use. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:list`:

``neos.neos:user:list``
***********************

**List all users**

This command lists all existing Neos users.







.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:removerole`:

``neos.neos:user:removerole``
*****************************

**Remove a role from a user**

This command allows for removal of a specific role from an existing user.

If an authentication provider was specified, the user will be determined by an account identified by "username"
related to the given provider. However, once a user has been found, the role will be removed from **all**
existing accounts related to that user, regardless of its authentication provider.

Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role to be removed from the user, for example "Neos.Neos:Administrator" or just "Administrator



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:setpassword`:

``neos.neos:user:setpassword``
******************************

**Set a new password for the given user**

This command sets a new password for an existing user. More specifically, all accounts related to the user
which are based on a username / password token will receive the new password.

If an authentication provider was specified, the user will be determined by an account identified by "username"
related to the given provider.

Arguments
^^^^^^^^^

``--username``
  Username of the user to modify
``--password``
  The new password



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use for finding the user. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:user:show`:

``neos.neos:user:show``
***********************

**Shows the given user**

This command shows some basic details about the given user. If such a user does not exist, this command
will exit with a non-zero status code.

The user will be retrieved by looking for a Neos backend account with the given identifier (ie. the username)
and then retrieving the user which owns that account. If an authentication provider is specified, this command
will look for an account identified by "username" for that specific provider.

Arguments
^^^^^^^^^

``--username``
  The username of the user to show. Usually refers to the account identifier of the user's Neos backend account.



Options
^^^^^^^

``--authentication-provider``
  Name of the authentication provider to use. Example: "Neos.Neos:Backend





.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:create`:

``neos.neos:workspace:create``
******************************

**Create a new workspace**

This command creates a new workspace.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace, for example "christmas-campaign



Options
^^^^^^^

``--base-workspace``
  Name of the base workspace. If none is specified, "live" is assumed.
``--title``
  Human friendly title of the workspace, for example "Christmas Campaign
``--description``
  A description explaining the purpose of the new workspace
``--owner``
  The identifier of a User to own the workspace





.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:delete`:

``neos.neos:workspace:delete``
******************************

**Deletes a workspace**

This command deletes a workspace. If you only want to empty a workspace and not delete the
workspace itself, use *workspace:discard* instead.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace, for example "christmas-campaign



Options
^^^^^^^

``--force``
  Delete the workspace and all of its contents



Related commands
^^^^^^^^^^^^^^^^

``neos.neos:workspace:discard``
  Discard changes in workspace



.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:discard`:

``neos.neos:workspace:discard``
*******************************

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





.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:discardall`:

``neos.neos:workspace:discardall``
**********************************

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

``neos.neos:workspace:discard``
  Discard changes in workspace



.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:list`:

``neos.neos:workspace:list``
****************************

**Display a list of existing workspaces**









.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:publish`:

``neos.neos:workspace:publish``
*******************************

**Publish changes of a workspace**

This command publishes all modified, created or deleted nodes in the specified workspace to its base workspace.
If a target workspace is specified, the content is published to that workspace instead.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace containing the changes to publish, for example "user-john



Options
^^^^^^^

``--target-workspace``
  If specified, the content will be published to this workspace instead of the base workspace
``--verbose``
  If enabled, some information about individual nodes will be displayed
``--dry-run``
  If set, only displays which nodes would be published, no real changes are committed





.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:publishall`:

``neos.neos:workspace:publishall``
**********************************

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

``neos.neos:workspace:publish``
  Publish changes of a workspace



.. _`Neos Command Reference: NEOS.NEOS neos.neos:workspace:rebase`:

``neos.neos:workspace:rebase``
******************************

**Rebase a workspace**

This command sets a new base workspace for the specified workspace. Note that doing so will put the possible
changes contained in the workspace to be rebased into a different context and thus might lead to unintended
results when being published.

Arguments
^^^^^^^^^

``--workspace``
  Name of the workspace to rebase, for example "user-john
``--base-workspace``
  Name of the new base workspace







.. _`Neos Command Reference: NEOS.SITEKICKSTARTER`:

Package *NEOS.SITEKICKSTARTER*
------------------------------


.. _`Neos Command Reference: NEOS.SITEKICKSTARTER neos.sitekickstarter:kickstart:site`:

``neos.sitekickstarter:kickstart:site``
***************************************

**Kickstart a new site package**

This command generates a new site package with basic Fusion and Sites.xml

Arguments
^^^^^^^^^

``--package-key``
  The packageKey for your site
``--site-name``
  The siteName of your site







