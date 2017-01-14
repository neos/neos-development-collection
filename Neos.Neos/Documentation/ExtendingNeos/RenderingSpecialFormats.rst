===============================================
Rendering special formats (CSV, JSON, XML, ...)
===============================================

Rendering an RSS feed as XML or a document in a different format than HTML is possible by configuring a new route
and adding a Fusion path that renders the format.

Let's have a look at an example that introduce a ``vcard`` format to render an imaginary ``Person`` document node type.

Routing
=======

``Configuration/Routes.yaml`` in your site package::

	-
		name: 'Neos :: Frontend :: Document node with vCard format'
		uriPattern: '{node}.vcf'
		defaults:
			'@package': Neos.Neos
			'@controller': Frontend\Node
			'@action': show
			'@format': vcard
		routeParts:
			node:
				handler: Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface
		appendExceedingArguments: true

This will register a new route to nodes with the ``vcard`` format. URIs with that format will get an ``.vcf`` extension.

Global ``Configuration/Routes.yaml`` (before the Neos subroutes)::

	##
	# Site package subroutes

	-
	  name: 'MyPackage'
	  uriPattern: '<MyPackageSubroutes>'
	  subRoutes:
		'MyPackageSubroutes':
		  package: 'My.Package'

	##
	# Neos subroutes
	# ...

This will add the new route from the site package before the Neos subroutes.

Fusion
==========

The ``root`` case in the default Fusion will render every format that is different from ``html`` by rendering a path
with the format value.

Root.fusion::

	# Define a path for rendering the vcard format
	vcard = Neos.Fusion:Case {
		person {
			condition = ${q(node).is('[instanceof My.Package:Person]')}
			type = 'My.Package:Person.Vcard'
		}
	}

	# Define a prototype to render a Person document as a vcard
	prototype(My.Package:Person.Vcard) < prototype(Neos.Fusion:Http.Message) {
		# Set the Content-Type header
		httpResponseHead {
			headers.Content-Type = 'text/x-vcard;charset=utf-8'
		}
		content = My.Package:Person {
			templatePath = 'resource://My.Package/Private/Templates/NodeTypes/Person.Vcard.html'
			# Set additional variables for the template
		}
	}

