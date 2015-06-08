=================
Creating a Plugin
=================

A plugin is a special content element which is implemented by PHP code through
an `ActionController` of Flow.

Kickstarter
===========

A minimal plugin can be kickstarted as follows::

	./flow kickstart:plugin Vendor.FancyPlugin "Vendor's fancy plugin"

The command outputs a list of generated files and instructions how to integrate the
plugin on existing Neos-Sites. Afterwards you can insert the plugin as content elements
in the Neos-Site.

You can then use the standard `kickstart:*` commands to f.e. create Domain Models or Repositories::

	./flow kickstart:model Vendor.FancyPlugin Friend name:string nickname:string age:integer
	./flow kickstart:repository Vendor.FancyPlugin Friend
