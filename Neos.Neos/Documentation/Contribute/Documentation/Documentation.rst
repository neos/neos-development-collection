==================
Neos Documentation
==================

How it works
============

Our documentation is managed in a Neos instance at https://docs.neos.io/

We use Read The Docs (http://neos.readthedocs.org) to host the versioned API
documentation for Neos.

This service listens for commits on Github and automatically builds the
documentation for all branches.

The entire documentation of Neos is located inside the Neos development collection
(https://github.com/neos/neos-development-collection) and can be edited by forking
the repository, editing the files and creating a pull request.

reStructuredText
================

The markup language that is used by Sphinx is
[reStructuredText](http://docutils.sourceforge.net/rst.html), a plaintext
markup syntax that easy to edit using any text editor and provides the
possibility to write well organized documentations that can be rendered
in multiple output formats by e.g. Sphinx.

Sphinx
======

Sphinx is a generator that automates building documentations from reStructuredText
markup. It can produce HTML, LaTex, ePub, plain text and many more output formats.

As Sphinx is a python based tool, you can install it by using either pip:

``pip install -U sphinx``

or easy_install:

``easy_install -U sphinx``


Makefile
========

As Sphinx accepts many options to build the many output formats,
we included a `Makefile` to simplify the building process.

In order to use the commands you must already have Sphinx installed.

You can get an overview of the provided commands by::

    cd Neos.Neos/Documentation
    make help
