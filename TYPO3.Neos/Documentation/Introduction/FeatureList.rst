.. _feature-list:

============
Feature List
============

Managing pages
==============

In the Neos backend it's possible to create, edit and delete pages. In the content
module the pagetree can be found by clicking the `Pages` button in the left top corner.
Page properties can be set in the inspector panel (right pane of the screen).
The pagetree has also support for moving pages, creating, deleting and renaming them.

Content Editing
===============

In the content module you can add, remove and move content.
When an element is selected you can see (just like with pages) some properties on the right
pane (inspect panel). Here it's possible to configure properties of the element which can not
be edited inline.

During content editing the currently selected content path will be shown in the breadcrumb
(bottom bar). In the left part of this bar there's also a `inspect button`. Clicking this button
shows you the current overview of the content elements on the page and allows you to select them
in the hierarchical structure. Using the inspect button it's also possible to move content elements
by drag and drop.

When a content element can't be edited inline (like a HTML element) the element will be overlayed
to show inline editing is not possible. The options in the inspect panel will allow you to manage
those kind of elements.

Node Types
==========

The available node types is still being worked on. The currently supported list is:

* Headline
	A simple headline. The headline size can be chosen using the inline editor.
* Text
	A text. This element can be edited inline using the Aloha/Hallo editor.
* Image
	The image can be uploaded, cropped and sized using the controls in the inspect panel.
* Text with Image
	A text with an image, text can be edited inline like a normal text element.
	The image can be uploaded, cropped and sized using the controls in the inspect panel.
	The image position can be chosen.
* HTML
	Adds a snippet of HTML code to the page. Editing HTML has syntax highlighting. Editing
	can now be done by double clicking the element or clicking the `HTML Editor` button in the
	inspect panel.
* Plugin (not functional)
	Currently a plugin can be displayed on a page, but not added to the page using the user interface.
	A plugin is defined by TypoScript configuration and is basically just a normal MVC Controller
	from a Flow package.
* Structural elements
	The structural elements can be used to add a two or three column structure to your page, in which
	new content elements can be inserted.

Import Export
=============

Neos has full support for importing and exporting site content using
content stored in XML files. This can now for example be used to manage the full
site content, or just those parts which are not editable in the Neos
interface yet.
For using this feature you should use a so called `Site Package`. This is a normal
Flow Package containing a ``Resources/Private/Content/Sites.xml`` file which contains
a node structure in XML. For an example of such a file you can check the
`Sites.xml <http://git.typo3.org/Flow/Packages/NeosDemoTypo3Org.git?a=blob_plain;f=Resources/Private/Content/Sites.xml;hb=master>`_
of the Neos demo site.

The import can be used like::

	./flow site:import --package-key My.Site

The export works like::

	./flow site:export > pathToYourSites.xml

Related to these functions there's a convenience method for removing all site related content::

	./flow site:prune --confirmation TRUE

Workspaces
==========

Neos already has workspaces build in. Every user works in his personal workspace, and has
to publish his changes to become live. Publishing pages is done using the `Publish` button in
the top right corner of the content module.

Furthermore, there is a "Workspace" module which can be used for publishing individual nodes.

Multi Domain Support
====================

Using the command line tools it's possible to link a hostname to a site node, making it possible
to have a multi domain installation in Neos. This way you can for example create a multilingual
website using a 'multi-tree concept'.

.. note:: There are still a few bugs related to URI resolving in this area; it needs to be more thoroughly tested.

Security
========

Using the Flow Security Framework we have a good support for security in Neos,
even though we're missing some parts of the interface. For example for configuring
access to content elements or pages, this is still being worked on. By using the
Security Framework there's support for roles and access to methods or content.
Of course there's also session handling and a login and logout functionality.

User Management
===============

It's possible to do basic user management by using a backend module or CLI.

Backend Module
--------------

Currently the user management in the Neos interface is currently only
available for users with the `Administrator` role (or via CLI).
Using the ``Management > User Settings`` module it's possible to change
your own personal settings, like your password. The ``Administration > User
Management`` to create, edit and delete users for the Neos backend.
Assigning roles is not yet possible from within the backend but is planned.

CLI
---

Using the commandline commands of Neos it's possible to create users by
using the ``user:create`` command. With ``user:addrole`` and ``user:removerole``
it's possible to assign or remove roles.
Removing and disabling users using CLI is planned.

Other Features
==============

Probably this feature list is not yet complete; as it has not fully been
updated to Neos yet. So feel free to contribute.