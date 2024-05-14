@flowEntities
Feature: Tests for the ContentCacheFlusher and cache flushing on node and nodetype specific tags

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
    'Neos.Neos:Test.DocumentType2':
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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                 | initialPropertyValues                            | nodeName |
      | a               | root                  | Neos.Neos:Site               | {}                                               | site     |
      | a1              | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1", "title": "Node a1"}     | a1       |
      | a1-1            | a1                    | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1-1", "title": "Node a1-1"} | a1-1     |
      | a2              | a                     | Neos.Neos:Test.DocumentType2 | {"uriPathSegment": "a2", "title": "Node a2"}     | a2       |
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
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DocumentType1) < prototype(Neos.Fusion:Component) {

      cacheVerifier = ${null}
      title = ${q(node).property('title')}

      renderer = afx`
        cacheVerifier={props.cacheVerifier},
        title={props.title}
      `

      @cache {
        mode = 'cached'
        entryIdentifier {
          documentNode = ${Neos.Caching.entryIdentifierForNode(node)}
        }
        entryTags {
          1 = ${Neos.Caching.nodeTag(node)}
          2 = ${Neos.Caching.descendantOfTag(node)}
        }
      }
    }

    prototype(Neos.Neos:Test.DocumentType2) < prototype(Neos.Fusion:Component) {

      cacheVerifier = ${null}
      title = ${q(node).property('title')}

      renderer = afx`
        cacheVerifier={props.cacheVerifier},
        title={props.title}
      `

      @cache {
        mode = 'cached'
        entryIdentifier {
          documentNode = ${Neos.Caching.entryIdentifierForNode(node)}
        }
        entryTags {
          1 = ${Neos.Caching.nodeTag(node)}
          2 = ${Neos.Caching.nodeTypeTag('Neos.Neos:Document',node)}
        }
      }
    }

    """


  Scenario: ContentCache gets flushed when a property of a node has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is a1

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a1
    """

    When the command SetNodeProperties is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "a1"                     |
      | propertyValues  | {"title": "Node a1 new"} |
    And the graph projection is fully up to date

    And the Fusion context node is a1
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, title=Node a1 new
    """

  Scenario: ContentCache gets not flushed when a property of another node has changed
    Given I have Fusion content cache enabled
    And the Fusion context node is a1

    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a1
    """

    When the command SetNodeProperties is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "a2"                     |
      | propertyValues  | {"title": "Node a2 new"} |
    And the graph projection is fully up to date

    And the Fusion context node is a1
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a1
    """

  Scenario: ContentCache gets flushed when a property of a node has changed by NodeType name
    Given I have Fusion content cache enabled
    And the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a2
    """

    When the command SetNodeProperties is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "a1"                     |
      | propertyValues  | {"title": "Node a1 new"} |
    And the graph projection is fully up to date

    And the Fusion context node is a2
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType2 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, title=Node a2
    """

  Scenario: ContentCache gets flushed when a property of a node has changed of a descendant node
    Given I have Fusion content cache enabled
    And the Fusion context node is "a1"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"first execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=first execution, title=Node a1
    """

    When the command SetNodeProperties is executed with payload:
      | Key             | Value                      |
      | contentStreamId | "cs-identifier"            |
      | nodeAggregateId | "a1-1"                     |
      | propertyValues  | {"title": "Node a1-1 new"} |
    And the graph projection is fully up to date

    And the Fusion context node is "a1"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DocumentType1 {
      cacheVerifier = ${"second execution"}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    cacheVerifier=second execution, title=Node a1
    """
