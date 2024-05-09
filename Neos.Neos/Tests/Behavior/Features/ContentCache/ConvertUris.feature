@flowEntities
Feature: Tests for the ContentCacheFlusher and cache flushing on DynamicNodeTag tags

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
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                 | initialPropertyValues                        | nodeName |
      | a               | root                  | Neos.Neos:Site               | {}                                           | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1", "title": "Node a1"} | a1       |
      | a2              | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a2", "title": "Node a2"} | a2       |
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And A site exists for node name "a" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And the Fusion context node is "a1"
    And the Fusion context request URI is "http://localhost"
    And the Fusion renderingMode is "frontend"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DocumentType1) < prototype(Neos.Fusion:Component) {

      cacheVerifier = ${null}
      title = ${q(node).property('title')}
      link = Neos.Neos:ConvertUris {
        value = ${"Some value with node URI: node://a1."}
      }

      renderer = afx`
        cacheVerifier={props.cacheVerifier},
        title={props.title},
        link={props.link}
      `

      @cache {
        mode = 'cached'
        entryIdentifier {
          documentNode = ${Neos.Caching.entryIdentifierForNode(node)}
        }
        entryTags {
          1 = ${Neos.Caching.nodeTag(node)}
        }
      }
    }

    """


  Scenario: ContentCache gets flushed when target node changes
    Given I have Fusion content cache enabled
    And the Fusion context node is a2

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a2, link=Some value with node URI: /a1.
    """

    When the command SetNodeProperties is executed with payload:
      | Key             | Value                        |
      | contentStreamId | "cs-identifier"              |
      | nodeAggregateId | "a1"                         |
      | propertyValues  | {"uriPathSegment": "a1-new"} |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date

    And the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, title=Node a2, link=Some value with node URI: /a1-new.
    """

  Scenario: ContentCache doesn't get flushed when target node changes in different workspace
    Given I have Fusion content cache enabled
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the Fusion context node is a2

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a2, link=Some value with node URI: /a1.
    """

    And I am in the active content stream of workspace "user-test" and dimension space point {}
    When the command SetNodeProperties is executed with payload:
      | Key             | Value                        |
      | contentStreamId | "cs-identifier"              |
      | nodeAggregateId | "a1"                         |
      | propertyValues  | {"uriPathSegment": "a1-new"} |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date

    And I am in the active content stream of workspace "live" and dimension space point {}
    And the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a2, link=Some value with node URI: /a1.
    """
