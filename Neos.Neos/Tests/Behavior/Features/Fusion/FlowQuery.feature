@flowEntities @contentrepository
Feature: Tests for the "Neos.Neos:Menu" and related Fusion prototypes

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
        _hiddenInIndex:
          type: bool
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Content':
      properties:
        title:
          type: string
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2a':
      superTypes:
        'Neos.Neos:Test.DocumentType2': true
    'Neos.Neos:Test.Content':
      superTypes:
        'Neos.Neos:Content': true

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | contentStreamId | "cs-identifier"   |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                  | initialPropertyValues                                                  | nodeName |
      | a               | root                  | Neos.Neos:Site                | {"title": "Node a"}                                                    | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1", "title": "Node a1"}                           | a1       |
      | a1a             | a1                    | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a", "title": "Node a1a"}                         | a1a      |
      | a1a1            | a1a                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a1", "title": "Node a1a1"}                       | a1a1     |
      | a1a2            | a1a                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a2", "title": "Node a1a2"}                       | a1a2     |
      | a1a3            | a1a                   | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a3", "title": "Node a1a3"}                       | a1a3     |
      | a1a4            | a1a                   | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a4", "title": "Node a1a4"}                       | a1a4     |
      | a1a5            | a1a                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1a5", "title": "Node a1a5"}                       | a1a5     |
      | a1b             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b", "title": "Node a1b4"}                        | a1b      |
      | a1b1            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1", "title": "Node a1b1"}                       | a1b1     |
      | a1b1a           | a1b1                  | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1b1a", "title": "Node a1b1a"}                     | a1b1a    |
      | a1b1b           | a1b1                  | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1b", "title": "Node a1b1b"}                     | a1b1b    |
      | a1b2            | a1b                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1b2", "title": "Node a1b2"}                       | a1b2     |
      | a1b3            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b3", "title": "Node a1b3"}                       | a1b3     |
      | a1c             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c", "title": "Node a1c", "_hiddenInIndex": true} | a1c      |
      | a1c1            | a1c                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c1", "title": "Node a1c1"}                       | a1c1     |
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
    And the Fusion context node is "a1a3"
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.NodeIds) < prototype(Neos.Fusion:Component) {
      nodes = ${value}
      renderer = Neos.Fusion:Loop {
        items = ${props.nodes}
        itemName = 'node'
        itemRenderer = ${node.nodeAggregateId.value}
        @glue = ','
      }
    }


    """

  Scenario: Parents
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:Join {
      @glue = ${String.chr(10)}
      noFilter = ${q(node).parents().get()}
      noFilter.@process.aslist = Neos.Neos:Test.NodeIds
      withFilter = ${q(node).parents('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      withFilter.@process.aslist = Neos.Neos:Test.NodeIds
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1a,a1,a
    a1
    """

  Scenario: ParentsUntil
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:Join {
      @glue = ${String.chr(10)}
      //noFilter = ${q(node).parentsUntil().get()}
      //noFilter.@process.aslist = Neos.Neos:Test.NodeIds
      criteria = ${q(node).parentsUntil('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      criteria.@process.aslist = Neos.Neos:Test.NodeIds
      filter = ${q(node).parentsUntil('[instanceof Neos.Neos:Test.DocumentType1]','[instanceof Neos.Neos:Test.DocumentType2]').get()}
      filter.@process.aslist = Neos.Neos:Test.NodeIds
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a1a,a1,a
    a1a,a1
    a1
    """

  Scenario: Closest
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:Join {
      @glue = ${String.chr(10)}
      upToSite = ${q(node).closest('[instanceof Neos.Neos:Site]').get()}
      upToSite.@process.aslist = Neos.Neos:Test.NodeIds
      currentNode = ${q(node).closest('[instanceof Neos.Neos:Test.DocumentType2a]').get()}
      currentNode.@process.aslist = Neos.Neos:Test.NodeIds
    }
    """
    Then I expect the following Fusion rendering result:
    """
    a
    a1a3
    """

  Scenario: Filter
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:Join {
      @glue = ${String.chr(10)}
      filterSite = ${q([documentNode, node, site]).filter('[instanceof Neos.Neos:Site]').get()}
      filterSite.@process.aslist = Neos.Neos:Test.NodeIds
      filterDocument = ${q(node).closest('[instanceof Neos.Neos:Document]').get()}
      filterDocument.@process.aslist = Neos.Neos:Test.NodeIds
      filterProperty = ${q(node).closest('[uriPathSegment="a1a3"]').get()}
      filterProperty.@process.aslist = Neos.Neos:Test.NodeIds

    }
    """
    Then I expect the following Fusion rendering result:
    """
    a
    a,a1a3,a1a3
    a1a3
    """
