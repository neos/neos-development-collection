.. _user-guide-workspaces:

==========
Workspaces
==========

All content in Neos lives in *Workspaces*. The publicly visible content comes from a workspace called *live*,
and any editing takes place in other workspaces. Edits will only become visible on the live website if they
are published to the *live* workspace.

That means workspaces can be seen as a way to group content based on it's state in the editing process. Workspaces
behave like transparent sheets of paper: On your own sheet you can draw new things, but the *live* sheet below
is still visible (and due to it's magic nature even updates of other editors publish changes to it).

Workspaces can also be stacked: between the *live* workspace and an editor's own workspace can be yet another
workspace that is shared between editors to allow collaboration on bigger changes as well as the review of
changes before they are published. The workspace a workspace is based on is called *base workspace*.

Terminology
===========

Public Workspace
  A public workspace has no owner and is not based on another workspace. Usually there is one public workspace
  name *live*: it contains the content that is visible to the visitors of a Neos-driven website.
Internal Workspace
  An internal workspace has no specific owner and is shared between editors. Internal workspaces are used to
  collaborate on bigger changes, like preparing a sales campaign.
Private Workspace
  Private workspaces are owned by a specific editor and only visible to that editor (and those having the
  administrator role). They can be used to shelve work temporarily, for example.
Personal Workspace
  Every editor has exactly one personal workspace. Any editing goes to that workspace first, no matter what.
  This personal workspace is only accessible by its owner.

Managing Workspaces
===================

From the user side workspace management is rather simple. New workspaces can be created in the Workspaces module.
As soon as more than one workspace is available, a new option will appear in the publish button of the content
module. It allows to switch the workspace that is being worked on (in more technical terms it will re-base the
personal workspace onto the selected one) and that will be published to.

.. note::
  To be able to switch the base workspace, there must be no pending changes in the personal workspace.

In the Workspaces module a list of existing workspaces is shown. That list shows the base workspace and owner
as well as a quick statistics view of the unpublished changes in each workspace. Depending on permissions buttons
allow to review changes, edit a workspace or delete it.

When changes in a workspace are reviewed, a list of those changes is shown and they can be published or discarded
completely or selectively.

Permissions
-----------

Out-of-the-box

- all users can create new private workspaces.
- all users can edit/delete their own private workspaces.
- users with the `Neos.Neos:RestrictedEditor` role can only publish to internal or private workspaces.
- other users (having the `Neos.Neos:Editor` role) can also publish to the public workspace *live* and
  create new internal workspaces.
- Administrators (having the `Neos.Neos:Administrator` role) can create internal workspaces and manage
  (edit and delete) internal as well as private workspaces.

Workflow Examples
=================

Doing some quick edits
----------------------

**Publish to live directly**

This is a very quick and easy workflow for editors that may publish to the *live* workspace. Just do any edits
in your personal workspace and publish to live when you are done.

**Using a review workspace**

If publishing to live is not allowed or a review of the changes is desired, an internal or private workspace can
be created. The changes are published to that workspace first and can then be reviewed and published to live by
a reviewer or administrator.

Prepare a new section
---------------------

If a new section for the website is to be prepared collaboratively, a new internal workspace needs to be
created. Everyone working on the new section switches their personal workspace to use the internal workspace
as base workspace and published changes to it. As soon as everything is done the changes can be reviewed and
published.

If some edits need to be made to other parts of the website in between, the personal workspace base can be
switched to the *live* or another workspace as needed. This allows to do independent edits without conflicts.
