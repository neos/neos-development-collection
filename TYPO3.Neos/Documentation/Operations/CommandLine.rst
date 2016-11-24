.. _operations-commandline:

==================
Command Line Tools
==================

Neos comes with a number of command line tools to ease setup and maintenance. These tools can be used
manually or be added to automated deployments or cron jobs. This section gives a high level overview of
the available tools.

More detailed instructions on the use of the command line tools can be displayed using the ``help`` command:

.. code-block:: shell

  ./flow help                     # lists all available command
  ./flow help <packageKey>        # lists commands provided in package
  ./flow help <commandIdentifier> # show help for specific command

Here is an example:

.. code-block:: none

  ./flow help user:addrole

  Add a role to a user

  COMMAND:
    typo3.neos:user:addrole

  USAGE:
    ./flow user:addrole [<options>] <username> <role>

  ARGUMENTS:
    --username           The username of the user
    --role               Role to be added to the user, for example
                         "Neos.Neos:Administrator" or just "Administrator

  OPTIONS:
    --authentication-provider Name of the authentication provider to use. Example:
                         "Typo3BackendProvider

  DESCRIPTION:
    This command allows for adding a specific role to an existing user.

    Roles can optionally be specified as a comma separated list. For all roles provided by Neos, the role
    namespace "Neos.Neos:" can be omitted.

    If an authentication provider was specified, the user will be determined by an account identified by "username"
    related to the given provider. However, once a user has been found, the new role will be added to all
    existing accounts related to that user, regardless of its authentication provider.

User Management
===============

These commands allow to manage users. To create an user with administrative privileges, this is needed:

.. code-block:: shell

  ./flow user:create john@doe.com pazzw0rd John Doe --roles Neos.Neos:Administrator

=======================================  ========================================
Command                                  Description
=======================================  ========================================
user:list                                List all users
user:show                                Shows the given user
user:create                              Create a new user
user:delete                              Delete a user
user:activate                            Activate a user
user:deactivate                          Deactivate a user
user:setpassword                         Set a new password for the given user
user:addrole                             Add a role to a user
user:removerole                          Remove a role from a user
=======================================  ========================================

Workspace Management
====================

The commands to manage workspaces reflect what is possible in the Neos user interface. They allow to list,
create and delete workspaces as well as publish and discard changes.

One notable difference is that rebasing a workspace is possivle from the command line *even if it contains
unpublished changes*.

=======================================  ========================================
Command                                  Description
=======================================  ========================================
workspace:publish                        Publish changes of a workspace
workspace:discard                        Discard changes in workspace
workspace:create                         Create a new workspace
workspace:delete                         Deletes a workspace
workspace:rebase                         Rebase a workspace
workspace:list                           Display a list of existing workspaces
=======================================  ========================================

Site Management
===============

=======================================  ========================================
Command                                  Description
=======================================  ========================================
domain:add                               Add a domain record
domain:list                              Display a list of available domain
                                         records
domain:delete                            Delete a domain record
domain:activate                          Activate a domain record
domain:deactivate                        Deactivate a domain record
site:import                              Import sites content
site:export                              Export sites content
site:prune                               Remove all content and related data
site:list                                Display a list of available sites
=======================================  ========================================
