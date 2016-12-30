==================
Extending the Page
==================

In Neos the page is a simple Node Type named Neos.Neos:Page, you can directly extend this Node Type to add specific
properties. Below you will find a simple example for adding a page background image:

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

	'Neos.Neos.NodeTypes:Page':
	  ui:
	    inspector:
	      groups:
	        background:
	          label: 'Background'
	          position: 900
	  properties:
	    backgroundImage:
	      type: Neos\Media\Domain\Model\ImageInterface
	      ui:
	        label: 'Image'
	        reloadPageIfChanged: TRUE
	        inspector:
	          group: 'background'


With this configuration, when you click on the page, you will see the Image editor in the Inspector.

To access the backgroundImage in your page template you can also modify the Neos.Neos:Page Fusion object, like
in the below example:

Fusion (Sites/Vendor.Site/Resources/Private/Fusion/Root.fusion) ::

	prototype(Neos.Neos:Page) {
		backgroundImage = ${q(node).property('backgroundImage')}
	}

With Neos.Media ViewHelper you can display the Image with the follwing HTML snippet:

HTML ::

	{namespace media=Neos\Media\ViewHelpers}
	<style>
	html {
		margin:0;
		padding:0;
		background: url({media:uri.image(image:backgroundImage)}) no-repeat center fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
	}
	</style>
