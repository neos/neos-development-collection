============
Installation
============

.. tip::

	TYPO3 Phoenix is built on top of the FLOW3 framework. If you run into technical problems,
	feel free to check the FLOW3 documentation for possible hints as well.

Requirements
------------

The following minimum requirements are operating system independent. Developers report working
setups on Debian Linux, MacOS, Windows Vista and Windows 7 (Windows should support symbolic
links).

* PHP 5.3.2 or newer (but not PHP 6)

	* Modules: mbstring, pdo_sqlite
	* Configuration: magic_quotes_gpc = off

* A web server, one of:

	* Apache

		* Apache modules: mod_rewrite
		* Apache configuration: AllowOverride FileInfo

	* IIS7 on Windows 7 with URL Rewrite has succesfully been tested as well.
	* Cherokee under Mac OS 10.6 (Snow Leopard) is working fine.

Other webservers could work, but have not been tested until now.

Installation of TYPO3 Phoenix
-----------------------------

* Download the TYPO3 Phoenix Sprint Release distribution from `TYPO3 Phoenix Download`_
* Unpack it to the document root of the webserver (often "htdocs")

.. note::

	It is highly recommended to change the document root of the webserver to the `Web` subfolder
	of the distribution!

* The webserver needs write permissions for some subfolders. FLOW3 Provides a command which sets
	the permissions. Change to the top level folder of the distribution, from there call the
	command `./flow3 flow3:core:setfilepermissions`. Execute this command providing the CLI user,
	webserver user and webserver group as parameters. On Debian Linux (Lenny) the commando
	would look like this: ::

		./flow3 flow3:core:setfilepermissions johndoe www-data www-data

* Now, just go to `http://your.phoenix/uri` and wait (this may take some time on the first call,
	because FLOW3 needs to initialize itself and to create caching data). You will get a notice
	that TYPO3 has not been initialized yet. Follow the instructions to import some demo content,
	and set your desired Backend Username and Password.

* Now you can check if everything works by pointing your browser to `http://your.phoenix/uri`.
	If all is well you should see the start page of the TYPO3 Phoenix demo site:

.. figure:: /Images/Quickstart/StartPage.png
	:align: right
	:width: 200pt
	:alt: The TYPO3 Phoenix start page

	The TYPO3 Phoenix start page

.. _TYPO3 Phoenix Download: http://phoenix.typo3.org/download
