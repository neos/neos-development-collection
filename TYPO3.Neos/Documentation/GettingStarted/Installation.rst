============
Installation
============

.. tip::

	TYPO3 Phoenix is built on top of the FLOW3 framework. If you run into technical problems,
	feel free to check the `FLOW3 documentation`_ for possible hints as well.

.. note::

	The setup script is still being worked on, and can throw an error during the installation steps.
	If this happens, please be patient and use the back button to return to the last step or
	reload the page. Polishing the setup procedure is planned for the current sprint.

Requirements
------------

Of course TYPO3 Phoenix has at least the same system requirements as FLOW3. You can find them
on the `FLOW3 Requirements Documentation`_.

Installation of TYPO3 Phoenix
-----------------------------

.. note::

	If you want to use the current master branch of TYPO3 Phoenix we suggest to use the
	TYPO3 Phoenix Base Distribution: `git://git.typo3.org/TYPO3v5/Distributions/Base.git`

* Follow the `Downloading FLOW3`, `Database Setup` and `Setting File Permissions` steps of the
	`FLOW3 GettingStarted`_, but use the `TYPO3 Phoenix Download`_ distribution package instead of
	the FLOW3 Base Distribution.

* Now go to `http://your.phoenix.uri/setup` and wait (this may take some time). You are now asked
	for a password, which you find in the blue box in the form. Copy paste this password into
	the field and press `Login`. You do not have to remember this password unless you would want to reinstall
	at a later point.

* Next step will ask you about the database credentials to use for your TYPO3 Phoenix installation.
	Set those credentials, select a database and click `Next`.

* Next step is setting your personal information and creating an administrator account. Set all values
	and click `Next`.

* On the `Import a site` page you select the `phoenix.demo.typo3.org` site and click `Next`.

If all went well you'll get a confirmation the setup is completed. Click on `Go to homepage` to see
	your TYPO3 Phoenix Demo Site:

.. figure:: /Images/GettingStarted/StartPage.png
	:align: right
	:width: 200pt
	:alt: The TYPO3 Phoenix start page

	The TYPO3 Phoenix start page

.. _TYPO3 Phoenix Download: http://phoenix.typo3.org/download.html
.. _FLOW3 Documentation: http://flow3.typo3.org/documentation/GettingStarted.html
.. _FLOW3 Requirements Documentation: http://flow3.typo3.org/documentation/guide/partii/requirements.html
.. _FLOW3 GettingStarted: http://flow3.typo3.org/documentation/GettingStarted.html