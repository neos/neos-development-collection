==================
Neos Documentation
==================

How it works
============

We use Read The Docs (http://neos.readthedocs.org) to host the documentation
for Neos.
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

``pip install -U Sphinx``

or easy_install:

``easy_install -U Sphinx``


Makefile
========

As Sphinx accepts many options to build the many output formats,
we included a `Makefile` to simplify the building process.

In order to use the commands you must already have Sphinx installed.

You can get an overview of the provided commands by

``cd Neos.Neos/Documentation``

``make help``


Docker
======

If you don't want to install Sphinx on your computer or have trouble installing
it, you can use a prebuilt Docker image that contains a working version of Sphinx.
The image is built on top of a pretty small alpine linux and has only around 80MB.

You can simply prefix your `make` command with the following docker command:

``docker run -v $(pwd):/documents hhoechtl/doctools-sphinx make html``

This will fire up a docker-container built from that image and execute the
Sphinx build inside the container. As your current directory is mounted into the
container, it can read the files and the generated output will be written in your
local filesystem as it would by just executing the make command with your local
Sphinx installation.
