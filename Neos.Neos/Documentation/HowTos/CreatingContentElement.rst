=================================
Creating a simple Content Element
=================================

If you need some specific content element, you can easly create a new Node Type with an attached HTML template. To add
a new Node Type, follow this example, just replace "Vendor" by your own vendor prefix:

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml)::

	'Vendor:YourContentElementName':
	  superTypes:
	    'Neos.Neos:Content': TRUE
	  ui:
	    label: 'My first custom content element'
	    group: 'general'
	    inspector:
	      groups:
	        image:
	          label: 'Image'
	          icon: 'icon-image'
	          position: 1
	  properties:
	    headline:
	      type: string
	      defaultValue: 'Replace by your headline value ...'
	      ui:
	        label: 'Headline'
	        inlineEditable: TRUE
	    subheadline:
	      type: string
	      defaultValue: 'Replace by your subheadline value ...'
	      ui:
	        label: 'Subheadline'
	        inlineEditable: TRUE
	    text:
	      type: string
	      ui:
	        label: 'Text'
	        reloadIfChanged: TRUE
	    image:
	      type: TYPO3\Media\Domain\Model\ImageInterface
	      ui:
	        label: 'Image'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'

Based on your Node Type configuration, now you need a TypoScript object to be able to use your new Node Type. This TypoScript
object needs to have the same name as the Node Type:

TypoScript (Sites/Vendor.Site/Resources/Private/Fusion/Root.fusion)::

	prototype(Vendor:YourContentElementName) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html'

		headline = ${q(node).property('headline')}
		subheadline = ${q(node).property('subheadline')}
		text = ${q(node).property('text')}
		image = ${q(node).property('image')}
	}

Last thing, add the required Fluid template:

HTML (Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html)::

	{namespace neos=Neos\Neos\ViewHelpers}
	{namespace media=TYPO3\Media\ViewHelpers}
	<article>
		<header>
			{neos:contentElement.editable(property: 'headline', tag: 'h2')}
			{neos:contentElement.editable(property: 'subheadline', tag: 'h3')}
		</header>
		<div>
			{neos:contentElement.editable(property: 'text')}
			<f:if condition="{image}"><media:image image="{image}" maximumWidth="300" alt="{headline}" /></f:if>
		</div>
	</article>

Now, if you try to add a new Node in your page, you should see your new Node Type. Enjoy editing with Neos.
