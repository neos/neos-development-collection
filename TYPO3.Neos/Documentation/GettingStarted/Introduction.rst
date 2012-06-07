============
Introduction
============

TYPO3 Phoenix
=============

Back in 2005 it was decided to start with a new TYPO3 version that should eventually
replace the current major version 4. In the long and twisted process that followed we
had to write a new framework first (FLOW3), struggle with the release schedule of PHP6,
fight tools not supporting PHP namespaces, and come up with good ideas for that
next-generation CMS we were about to build.

Now we actually have first releases of TYPO3 Phoenix, as it was codenamed back then –
and they are probably not what you expected. They are sprint releases, following an
iteration in our Scrum-based project management. As such they are complete releases,
doing everything they are supposed to do, *according to the goals of their sprint*.

They are not, releases of TYPO3 Phoenix, though - as you would probably laugh about the feature
set, then...

System Overview
===============

TYPO3 Phoenix is made up of packages and based on the FLOW3 framework. Most of the packages
are part of the FLOW3 base system, TYPO3 Phoenix consists of a few additional packages, like
TYPO3 and TypoScript, in which the actual CMS functionality is contained. The templates,
graphics and content of the demo site are in another package. Generally, all static website
resources (including templates, TypoScript and Images) can be found in separate packages for
each website.

.. figure:: /Images/GettingStarted/SystemStructure.png
	:align: right
	:width: 200pt
	:alt: The TYPO3 Phoenix system structure

	The TYPO3 Phoenix system structure
