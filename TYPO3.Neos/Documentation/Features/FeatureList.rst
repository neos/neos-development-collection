============
Feature List
============

Managing pages
==============

In the TYPO3 Phoenix backend it's possible to create, edit and delete pages. In the content
module the pagetree can be found by clicking the `Pages` button in the left top corner.
Page properties can be set in the inspector panel (right pane of the screen).
The pagetree has also support for moving pages, and uses a dropzone for deleting pages like
known from recent TYPO3 versions.

.. note::

	Moving nodes to a different parent is not yet supported.

Content Editing
===============

In the content module you can add, remove and move content. Copy currently works like cut,
and will thus not create a new element on paste, but just moves the element.
When an element is selected you can see (just like with pages) some properties on the right
pane (inspect panel). Here it's possible to configure properties of the element which can not
be edited inline.

During content editing the currently selected content path will be shown in the breadcrumb
(left bottom corner). In this same corner there's also a `inspect button`. Clicking this button
shows you the current overview of the content elements on the page (like the modified status).
Using the inspect button it's also possible to move content elements by drag and drop.

When a content element can't be edited inline (like a HTML element) the element will be overlayed
to show inline editing is not possible. The options in the inspect panel will allow you to manage
those kind of elements.

Content Types
=============

The available content types is still being worked on. The currently supported list is:

* Text
	A text with a headline. This element can be edited inline using the Aloha editor.
* Text with Image
	A text with headline and an image, text can be edited inline like a normal text element.
	The image can be uploaded and cropped using the controls in the inspect panel.
* HTML
	Adds a snippet of HTML code to the page. Editing HTML has syntax highlighting. Editing
	can now be done by double clicking the element or clicking the `HTML Editor` button in the
	inspect panel.
* Plugin (not functional)
	Currently a plugin can be displayed on a page, but not added to the page using the user interface.
	A plugin is defined by TypoScript configuration and is basically just a normal MVC Controller
	from a FLOW3 package.
* Structural elements
	The structural elements can be used to add a two or three column structure to your page, in which
	new content elements can be inserted.

Import Export
=============

TYPO3 Phoenix has full support for importing and exporting site content using
content stored in XML files. This can now for example be used to manage the full
site content, or just those parts which are not edittable in the TYPO3 Phoenix
interface yet.
For using this feature you should use a so called `Site Package`. This is a normal
FLOW3 Package containing a ``Resources/Private/Content/Sites.xml`` file which contains
a node structure in XML. For an example of such a file you can check the
`Sites.xml <http://git.typo3.org/FLOW3/Packages/PhoenixDemoTypo3Org.git?a=blob_plain;f=Resources/Private/Content/Sites.xml;hb=master>`_
of the TYPO3 Phoenix demo site.

The import can be used like:

::

	./flow3 site:import --package-key My.Site

The export works like:

	./flow3 site:export > pathToYourSites.xml

Related to these functions there's a convenience method for removing all site related content:

	./flow3 site:prune --confirmation TRUE

Launcher
========

In the top of the backend is a bar called the `launcher`. This bar will become a quick entry point
to your TYPO3 Phoenix installation. Currently only the search is implemented. By typing keywords
in the launcher bar you can search for pages and content. Clicking the search results currently
fails, this will be resolved in one of the following releases.

Workspaces
==========

TYPO3 Phoenix already has workspace build in. Every user works in his personal workspace, and has
to publish his changes to become live. Publishing pages is done using the `Publish` button in
the top right corner of the content module.

Multi Domain Support
====================

Using the command line tools it's possible to link a hostname to a site node, making it possible
to have a multi domain installation in TYPO3 Phoenix. This way you can for example create a multilingual
website using a 'multi-tree concept'.

Security
========

Using the FLOW3 Security Framework we have a good support for security in TYPO3 Phoenix,
even though we're missing some parts of the interface. For example for configuring
access to content elements or pages, this is still being worked on. By using the
Security Framework there's support for roles and access to methods or content.
Of course there's also session handling and a login and logout functionality.

User Management
===============

It's possible to do basic user management by using a backend module or CLI.

Backend Module
--------------

Currently the user management in the TYPO3 Phoenix interface is currently only
available for users with the `Administrator` role (or via CLI).
Using the ``Management > User Settings`` module it's possible to change
your own personal settings, like your password. The ``Administration > User
Management`` to create, edit and delete users for the TYPO3 Phoenix backend.
Assigning roles is not yet possible from within the backend but is planned.

CLI
---

Using the commandline commands of TYPO3 Phoenix it's possible to create users by
using the ``user:create`` command. With ``user:addrole`` and ``user:removerole``
it's possible to assign or remove roles.
Removing and disabling users using CLI is planned.
