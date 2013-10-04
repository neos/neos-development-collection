=================================
Creating a simple Content Element
=================================

If you need some specific content element, you can easly create a new Node Type with an attached HTML template. To add
a new Node Type, follow this exemple, just replace "Vendor" by your own vendor prefix:

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml)::

	'Vendor:YourContentElementName':
	  superTypes:
	    - 'TYPO3.Neos:Content'
	  ui:
	    label: 'My first custom content element'
	    group: 'General'
	    inspector:
	      groups:
	        image:
	          label: 'Image'
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
	      type: TYPO3\Media\Domain\Model\ImageVariant
	      ui:
	        label: 'Image'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'

Based on your Node Type configuration, now you need a TypoScript object to be able to use your new Node Type. This TypoScript
object needs to have the same name as the Node Type:

TypoScript (Sites/Vendor.Site/Resources/Private/TypoScripts/Library/Root.ts2)::

	prototype(Vendor:YourContentElementName) < prototype(TYPO3.Neos:Template)
	prototype(Vendor:YourContentElementName) {
		templatePath = 'resource://Vendor.Site/Private/Templates/TypoScriptObjects/YourContentElementName.html'

		headline = ${q(node).property('headline')}
		subheadline = ${q(node).property('subheadline')}
		text = ${q(node).property('text')}
		image = ${q(node).property('image')}
	}

Last thing, add the required Fluid template:

HTML (Vendor.Site/Private/Templates/TypoScriptObjects/YourContentElementName.html)::

	{namespace neos=TYPO3\Neos\ViewHelpers}
	{namespace media=TYPO3\Media\ViewHelpers}
	<neos:contentElement node="{node}">
		<article>
			<header>
				<h2><neos:contentElement.editable property="headline">{headline -> f:format.raw()}</neos:contentElement></h2>
				<h3><neos:contentElement.editable property="subheadline">{subheadline -> f:format.raw()}</neos:contentElement></h3>
			</header>
			<div>
				<neos:contentElement.editable property="text">{text -> f:format.raw()}</neos:contentElement.editable>
				<media:image image="{image}" maximumWidth="300" alt="{headline}" />
			</div>
		</article>
	</neos:contentElement>

Now, if you try to add a new Node in your page, you should see your new Node Type. Enjoy editing with Neos.