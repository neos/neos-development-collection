===================================
Wrapping a List of Content Elements
===================================

Create a simple Wrapper that can contain multiple content Elements.

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

  'Vendor:Box':
    superTypes:
      'Neos.Neos:Content': TRUE
    ui:
      group: structure
      label: Box
      icon: icon-columns
      inlineEditable: true
    childNodes:
      column0:
        type: 'Neos.Neos:ContentCollection'

TypoScript (Sites/Vendor.Site/Resources/Private/Fusion/NodeTypes.fusion) ::

	prototype(Vendor:Box) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://Vendor.Site/Private/Templates/FusionObjects/Box.html'
		columnContent = Neos.Neos:ContentCollection
		columnContent {
			nodePath = 'column0'
		}
	}

Html (Sites/Vendor.Site/Private/Templates/FusionObjects/Box.html) ::

	{namespace ts=TYPO3\TypoScript\ViewHelpers}

	<div class="container box">
		<div class="column">
			<ts:render path="columnContent" />
		</div>
	</div>


Extending it to use an option
=============================

You can even simply extend the box to provide a checkbox for different properties.

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

  'Vendor:Box':
    superTypes:
      'Neos.Neos:Content': TRUE
    ui:
      group: structure
      label: Box
      icon: icon-columns
      inlineEditable: TRUE
      inspector:
        groups:
          display:
            label: Display
            position: 5
    properties:
      collapsed:
        type: boolean
        ui:
          label: Collapsed
          reloadIfChanged: TRUE
          inspector:
            group: display
    childNodes:
      column0:
        type: 'Neos.Neos:ContentCollection'

TypoScript (Sites/Vendor.Site/Resources/Private/Fusion/NodeTypes.fusion) ::

	prototype(Vendor:Box) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://Vendor.Site/Private/Templates/FusionObjects/Box.html'
		columnContent = Neos.Neos:ContentCollection
		columnContent {
			nodePath = 'column0'
		}
		collapsed = ${q(node).property('collapsed')}
	}

Html (Sites/Vendor.Site/Private/Templates/FusionObjects/Box.html) ::

	{namespace ts=TYPO3\TypoScript\ViewHelpers}

	<f:if condition="{collapsed}">
		<button>open the collapsed box via js</button>
	</f:if>
	<div class="container box {f:if(condition: collapsed, then: 'collapsed', else: '')}">
		<div class="column">
			<ts:render path="columnContent" />
		</div>
	</div>
