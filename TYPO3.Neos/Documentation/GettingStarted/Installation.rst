============
Installation
============

.. tip::

	TYPO3 Neos is built on top of the TYPO3 Flow framework. If you run into technical problems,
	feel free to check the `TYPO3 Flow documentation`_ for possible hints as well.

Requirements
------------

Of course TYPO3 Neos has at least the same system requirements as TYPO3 Flow. You can find them
on the `TYPO3 Flow Requirements Documentation`_.

Installation of TYPO3 Neos
--------------------------

.. note::

	If you want to use the current master branch of TYPO3 Neos we suggest to use the
	TYPO3 Neos Base Distribution: `git://git.typo3.org/TYPO3v5/Distributions/Base.git`

* Fetch the `TYPO3 Neos Download`_ packages, or check it out from git using
  `git clone git://git.typo3.org/TYPO3v5/Distributions/Base.git`, followed by a
  `composer install`.

* Set up your virtual host to point to the Web/ directory of your Neos installation.

* Go to `http://your.neos.uri/setup`. This page checks the basic requirements. If they
  are met, you will be redirected to the setup tool.

* Then, follow the on-screen instructions of the setup tool.

* If all went well you'll get a confirmation the setup is completed, and you can enter the
  frontend or backend of your Neos website.

.. figure:: /Images/GettingStarted/StartPage.png
	:align: right
	:width: 200pt
	:alt: The TYPO3 Neos start page

	The TYPO3 Neos start page

.. _TYPO3 Neos Download: http://neos.typo3.org/download.html
.. _TYPO3 Flow Documentation: http://flow.typo3.org/documentation/GettingStarted.html
.. _TYPO3 Flow Requirements Documentation: http://flow.typo3.org/documentation/guide/partii/requirements.html
.. _TYPO3 Flow GettingStarted: http://flow.typo3.org/documentation/GettingStarted.html