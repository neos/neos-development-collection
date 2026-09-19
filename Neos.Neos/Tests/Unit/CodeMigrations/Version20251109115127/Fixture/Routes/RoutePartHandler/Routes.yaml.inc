- name:  'ATOM Package Feed'
  uriPattern: '{node}.atom'
  defaults:
    '@package': 'Neos.Neos'
    '@controller': 'Frontend\Node'
    '@action': 'show'
    '@format': 'atom'
  routeParts:
    'node':
       handler: 'Neos\Neos\Routing\FrontendNodeRoutePartHandler'
-
  name: Preview
  uriPattern: neos/preview
  defaults:
    '@action': preview
  appendExceedingArguments: true
-
  name: 'Default Frontend'
  uriPattern: '{node}'
  routeParts:
    'node':
      handler: 'Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface'
      options:
        uriPathSuffix: '<defaultUriSuffix>'
  appendExceedingArguments: true
-----
-
  name: 'ATOM Package Feed'
  uriPattern: '{node}.atom'
  defaults:
    '@package': Neos.Neos
    '@controller': Frontend\Node
    '@action': show
    '@format': atom
  routeParts:
    node:
      handler: Neos\Neos\FrontendRouting\FrontendNodeRoutePartHandlerInterface
-
  name: Preview
  uriPattern: neos/preview
  defaults:
    '@action': preview
  appendExceedingArguments: true
-
  name: 'Default Frontend'
  uriPattern: '{node}'
  routeParts:
    node:
      handler: Neos\Neos\FrontendRouting\FrontendNodeRoutePartHandlerInterface
      options:
        uriPathSuffix: '<defaultUriSuffix>'
  appendExceedingArguments: true
