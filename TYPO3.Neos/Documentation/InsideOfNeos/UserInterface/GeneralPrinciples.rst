=================================
General User Interface Principles
=================================

The following principles serve as general guiding concepts throughout the whole Neos product.

Overall User Interface Goals
============================

We have set up the following goals to strive for UI-wise:

* Reliable editing
* Predictable UI Behavior
* Immediate feedback for the user
* Built with the web - for the web

UI concepts should be evaluated against the above goals.

Technical guidelines / Goals
============================

When implementing the user interface, we should follow these guidelines on a technical side:

* Take the pragmatic approach
* Augment the frontend website
* No iFrame in the content module, generally no iFrames except for bigger modal dialogs
* Browser support >= IE9; in the prototyping phase focus on Chrome / Firefox
* No polling of data from the server!
* A reload should always take you back to a safe state

CSS Guidelines
==============

Overall Goal:

* Be pragmatic! We strive for solutions which work out-of-the-box in 95% of the cases; and tell the integrator
  how to solve the other 5%. Thus, the integrator has to care to make his CSS work with Neos; we do not use a sandbox.

Implementation notes:

* All CSS selectors should be fully lowercase, with ``-`` as separator. Example: ``neos-menu, neos-inspector``
* We use the ``neos-`` prefix
* The integrator is never allowed to override ``neos-``, ``typo3-`` and ``aloha-``
* The main UI elements have an ID, and a partial reset is used to give us predictable behavior inside them.
* We use *sass*. To install, use +gem install sass compass+. Then, before modifying CSS, go to css/ and run
  +sass --compass --watch style.scss:style.css+. This will update style.css at every modification of style.scss.
* We use r.js for generating the Includes-built.css file. The command used by the built server is
  ``r.js -o cssIn=Includes.css out=Includes-built.css``

Z-Indexes
---------

The Neos UI uses Z-Indexes starting at *10000*.

.. warning:: TODO: Formulate some more about the usage of z-indexes.
