@flowEntities
Feature: Tests for the "Neos.Neos:NodeUri" Fusion prototype

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues                        | nodeName |
      | a               | root                  | Neos.Neos:Site              | {"title": "Node a"}                          | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType | {"uriPathSegment": "a1", "title": "Node a1"} | a1       |
    And A site exists for node name "a" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'a':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And the Fusion context request URI is "http://localhost"
    And the Fusion renderingMode is "frontend"

  Scenario: Node uris
    And the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      uri = Neos.Neos:NodeUri {
        node = ${node}
      }
      link = Neos.Neos:NodeLink {
        node = ${node}
      }
      uriWithSection = Neos.Neos:NodeUri {
        node = ${node}
        section = 'foo'
      }
      uriWithAppendExceedingArguments = Neos.Neos:NodeUri {
        node = ${node}
        arguments = ${{q: 'abc'}}
      }
      absoluteUri = Neos.Neos:NodeUri {
        node = ${node}
        absolute = true
      }
      mixed = Neos.Neos:NodeUri {
        node = ${node}
        section = 'foo'
        arguments = ${{q: 'abc'}}
        absolute = true
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    uri: /a1
    link: <a href="/a1">Neos.Neos:Test.DocumentType (a1)</a>
    uriWithSection: /a1#foo
    uriWithAppendExceedingArguments: /a1?q=abc
    absoluteUri: http://localhost/a1
    mixed: http://localhost/a1?q=abc#foo
    """

  Scenario: Node as string node path syntax
    And the Fusion context node is "a"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      sitesRootPath = Neos.Neos:NodeUri {
        node = '/<Neos.Neos:Sites>/a/a1'
      }
      relativePath = Neos.Neos:NodeUri {
        node = 'a1'
      }
      nodeIdentifier = Neos.Neos:NodeUri {
        node = 'node://a1'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    sitesRootPath: /a1
    relativePath: /a1
    nodeIdentifier: /a1
    """

  Scenario: Node as legacy string node path syntax
    And the Fusion context node is "a"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      sitesRootPath = Neos.Neos:NodeUri {
        node = '/sites/a/a1'
      }
      siteRelativePath = Neos.Neos:NodeUri {
        node = '~/a1'
      }
      site = Neos.Neos:NodeUri {
        node = '~'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    sitesRootPath: /a1
    siteRelativePath: /a1
    site: /
    """
