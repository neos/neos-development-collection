@fixtures
Feature: Tests for the "Neos.ContentRepository" Flow Query methods.

  Background:
    Given I have the site "a"
    And I have the following NodeTypes configuration:
    """yaml
    'unstructured': {}
    'Neos.Neos:FallbackNode': {}
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Content':
      properties:
        title:
          type: string
    'Neos.Neos:Test.Site':
      superTypes:
        'Neos.Neos:Document': true
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
    And I have the following nodes:
      | Identifier | Path                       | Node Type                     | Properties                                         | Hidden in index |
      | root       | /sites                     | unstructured                  |                                                    | false           |
      | a          | /sites/a                   | Neos.Neos:Test.Site           | {"uriPathSegment": "a", "title": "Node a"}         | false           |
      | a1         | /sites/a/a1                | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1", "title": "Node a1"}       | false           |
      | a1a        | /sites/a/a1/a1a            | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a", "title": "Node a1a"}     | false           |
      | a1a1       | /sites/a/a1/a1a/a1a1       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a1", "title": "Node a1a1"}   | false           |
      | a1a2       | /sites/a/a1/a1a/a1a2       | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1a2", "title": "Node a1a2"}   | false           |
      | a1a3       | /sites/a/a1/a1a/a1a3       | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a3", "title": "Node a1a3"}   | false           |
      | a1a4       | /sites/a/a1/a1a/a1a4       | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a4", "title": "Node a1a4"}   | false           |
      | a1a5       | /sites/a/a1/a1a/a1a5       | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a5", "title": "Node a1a5"}   | false           |
      | a1a6       | /sites/a/a1/a1a/a1a6       | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1a6", "title": "Node a1a6"}   | false           |
      | a1a7       | /sites/a/a1/a1a/a1a7       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a7", "title": "Node a1a7"}   | false           |
      | a1b        | /sites/a/a1/a1b            | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b", "title": "Node a1b"}     | false           |
      | a1b1       | /sites/a/a1/a1b/a1b1       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1", "title": "Node a1b1"}   | false           |
      | a1b1a      | /sites/a/a1/a1b/a1b1/a1b1a | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1b1a", "title": "Node a1b1a"} | false           |
      | a1b1b      | /sites/a/a1/a1b/a1b1/a1b1b | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1b", "title": "Node a1b1b"} | false           |
      | a1b2       | /sites/a/a1/a1b/a1b2       | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1b2", "title": "Node a1b2"}   | false           |
      | a1b3       | /sites/a/a1/a1b/a1b3       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b3", "title": "Node a1b3"}   | false           |
      | a1c        | /sites/a/a1/a1c            | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c", "title": "Node a1c"}     | true            |
      | a1c1       | /sites/a/a1/a1c/a1c1       | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c1", "title": "Node a1c1"}   | false           |
    And the Fusion context node is "a1a4"
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.RenderNodes) < prototype(Neos.Fusion:Component) {
      nodes = ${value}
      renderer = Neos.Fusion:Loop {
        items = ${props.nodes}
        itemName = 'node'
        itemRenderer = ${node.identifier}
        @glue = ','
      }
    }

    prototype(Neos.Neos:Test.RenderStringDataStructure) < prototype(Neos.Fusion:Component) {
      items = ${value}
      renderer = Neos.Fusion:Loop {
        items = ${props.items}
        itemKey = 'key'
        itemName = 'string'
        itemRenderer = ${key + ':' + (string ? string + ' ' : '')}
        @glue = "\n"
      }
    }

    prototype(Neos.Neos:Test.RenderNodesDataStructure) < prototype(Neos.Fusion:Component) {
      items = ${value}
      renderer = Neos.Fusion:Loop {
        items = ${props.items}
        itemKey = 'key'
        itemName = 'nodes'
        itemRenderer = Neos.Fusion:Join {
          name = ${key + ':' + (nodes ? ' ' : '')}
          ids = Neos.Neos:Test.RenderNodes {
            nodes = ${nodes}
          }
        }
        @glue = "\n"
      }
    }
    """

  Scenario: Children
    When the Fusion context node is "a1a"
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).children().get()}
      withFilter = ${q(node).children('[instanceof Neos.Neos:Test.DocumentType2]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a1,a1a2,a1a3,a1a4,a1a5,a1a6,a1a7
    withFilter: a1a2,a1a3,a1a4,a1a5,a1a6
    """

  Scenario: Has
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      nodesWithDocumentChildren = ${q([node,site]).has('[instanceof Neos.Neos:Document]').get()}
      nodesWithContentChildren = ${q(node).has('[instanceof Neos.Neos:Content]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    nodesWithDocumentChildren: a
    nodesWithContentChildren:
    """

  Scenario: Parent
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).parent().get()}
      withFilter = ${q(node).parent('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a
    withFilter:
    """

  Scenario: Parents
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).parents().get()}
      withFilter = ${q(node).parents('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a,a1,a
    withFilter: a1
    """

  Scenario: ParentsUntil
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      criteria = ${q(node).parentsUntil('[instanceof Neos.Neos:Test.Site]').get()}
      # this does not work in Neos 8.3 but it should according to documentation and yield "a1a"
      # criteriaAndFilter = ${q(node).parentsUntil('[instanceof Neos.Neos:Test.DocumentType1]', '[instanceof Neos.Neos:Test.DocumentType2]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    criteria: a1a,a1
    """

  Scenario: Closest
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      upToType = ${q(node).closest('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      upToSite = ${q(node).closest('[instanceof Neos.Neos:Test.Site]').get()}
      currentNode = ${q(node).closest('[instanceof Neos.Neos:Test.DocumentType2a]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    upToType: a1
    upToSite: a
    currentNode: a1a4
    """

  Scenario: Filter
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      filterSite = ${q([documentNode, node, site]).filter('[instanceof Neos.Neos:Test.Site]').get()}
      filterDocument = ${q([documentNode, node, site]).filter('[instanceof Neos.Neos:Document]').get()}
      filterProperty = ${q([documentNode, node, site]).filter('[uriPathSegment="a1a4"]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    filterSite: a
    filterDocument: a1a4,a1a4,a
    filterProperty: a1a4,a1a4
    """

  Scenario: Prev
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).prev().get()}
      matchingFilter = ${q(node).prev('[instanceof Neos.Neos:Document]').get()}
      nonMatchingFilter = ${q(node).prev('[instanceof Neos.Neos:Site]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a3
    matchingFilter: a1a3
    nonMatchingFilter:
    """

  Scenario: Next
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).next().get()}
      matchingFilter = ${q(node).next('[instanceof Neos.Neos:Document]').get()}
      nonMatchingFilter = ${q(node).next('[instanceof Neos.Neos:Site]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a5
    matchingFilter: a1a5
    nonMatchingFilter:
    """
  Scenario: PrevAll
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).prevAll().get()}
      withFilter = ${q(node).prevAll('[instanceof Neos.Neos:Test.DocumentType2]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a1,a1a2,a1a3
    withFilter: a1a2,a1a3
    """

  Scenario: NextAll
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).nextAll().get()}
      withFilter = ${q(node).nextAll('[instanceof Neos.Neos:Test.DocumentType2]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a5,a1a6,a1a7
    withFilter: a1a5,a1a6
    """

  Scenario: NextUntil
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      criteria = ${q(node).nextUntil('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    criteria: a1a5,a1a6
    """

  Scenario: PrevUntil
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      criteria = ${q(node).prevUntil('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    criteria: a1a2,a1a3
    """

  Scenario: Siblings
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      noFilter = ${q(node).siblings().get()}
      withFilter = ${q(node).siblings('[instanceof Neos.Neos:Test.DocumentType1]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    noFilter: a1a1,a1a2,a1a3,a1a5,a1a6,a1a7
    withFilter: a1a1,a1a7
    """

  Scenario: Find
    When the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      typeFilter = ${q(node).find('[instanceof Neos.Neos:Test.DocumentType2]').get()}
      combinedFilter = ${q(node).find('[instanceof Neos.Neos:Test.DocumentType2][uriPathSegment*="b1"]').get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    typeFilter: a1a,a1b1a,a1a2,a1b2,a1a3,a1a4,a1a5,a1a6
    combinedFilter: a1b1a
    """
