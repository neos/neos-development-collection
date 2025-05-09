@flowEntities
Feature: Tests for the "Neos.Fusion:ActionUri" Fusion prototype

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

  Scenario: Build routes with nodes
    And the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      # Build a node's frontend route manually (NOT RECOMMENDED AT ALL)
      frontendUriWithNodeInstance = Neos.Fusion:ActionUri {
        package = 'Neos.Neos'
        controller = 'Frontend\\Node'
        action = 'show'
        arguments {
          node = ${node}
        }
      }
      previewUriWithNodeInstance = Neos.Fusion:ActionUri {
        package = 'Neos.Neos'
        controller = 'Frontend\\Node'
        action = 'preview'
        arguments {
          node = ${node}
        }
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    frontendUriWithNodeInstance: /a1
    previewUriWithNodeInstance: /neos/preview?node=%7B%22contentRepositoryId%22%3A%22default%22%2C%22workspaceName%22%3A%22live%22%2C%22dimensionSpacePoint%22%3A%5B%5D%2C%22aggregateId%22%3A%22a1%22%7D
    """
