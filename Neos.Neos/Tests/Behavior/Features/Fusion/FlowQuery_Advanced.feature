@flowEntities @contentrepository
Feature: Tests for Flow Query context operation

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
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
        hiddenInMenu:
          type: bool
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
    And I am in workspace "live" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                 | initialPropertyValues                            | nodeName |
      | a               | root                  | Neos.Neos:Site               | {"title": "Node a"}                              | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1", "title": "Node a1"}     | a1       |
      | a1a             | a1                    | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1a", "title": "Node a1a"}   | a1a      |
      | a1a1            | a1a                   | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1a1", "title": "Node a1a1"} | a1a1     |
      | a1a2            | a1a                   | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1a2", "title": "Node a1a2"} | a1a2     |
      | a1a3            | a1a                   | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a1a3", "title": "Node a1a3"} | a1a3     |

    # special nodes:

    When the command CreateWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "other-workspace" |
      | baseWorkspaceName  | "live"            |
      | newContentStreamId | "workspace-cs-id" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | workspaceName   | originDimensionSpacePoint | parentNodeAggregateId | nodeTypeName                 | initialPropertyValues                        |
      | a2-disabled     | live            | {"language":"en"}         | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a2", "title": "Node a2"} |
      | a3-workspace    | other-workspace | {"language":"en"}         | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a3", "title": "Node a3"} |
      | a4-german       | live            | {"language":"de"}         | a                     | Neos.Neos:Test.DocumentType1 | {"uriPathSegment": "a4", "title": "Node a4"} |

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value             |
      | nodeAggregateId              | "a2-disabled"     |
      | coveredDimensionSpacePoint   | {"language":"en"} |
      | nodeVariantSelectionStrategy | "allVariants"     |

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
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.RenderNodesDataStructure) < prototype(Neos.Fusion:Component) {
      items = ${value}
      renderer = Neos.Fusion:Loop {
        items = ${props.items}
        itemKey = 'key'
        itemName = 'nodes'
        itemRenderer = Neos.Fusion:Join {
          name = ${key + ':' + (nodes ? ' ' : '')}
          ids = Neos.Fusion:Loop {
            items = ${nodes}
            itemName = 'node'
            itemRenderer = ${node.aggregateId + '[' + node.workspaceName + ',' + Json.stringify(node.dimensionSpacePoint) + ']' + (Neos.Node.isDisabled(node) ? '*' : '')}
            @glue = ','
          }
        }
        @glue = "\n"
      }
    }
    """

  Scenario: Default context() operation case
    Workspace Live, Language EN, Visibility hidden

    And the Fusion context node is "a"
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      default = ${q(node).children().get()}
      explicit = ${q(node).context({'workspaceName': 'live', 'dimensions': {'language': ['en']}, 'invisibleContentShown': false}).children().get()}
      empty = ${q(node).context({}).children().get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    default: a1[live,{"language":"en"}]
    explicit: a1[live,{"language":"en"}]
    empty: a1[live,{"language":"en"}]
    """

  Scenario: context() operation
    And the Fusion context node is "a"

    #
    # Other workspaces
    #
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      children = ${q(node).context({'workspaceName': 'other-workspace'}).children().get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    children: a1[other-workspace,{"language":"en"}],a3-workspace[other-workspace,{"language":"en"}]
    """

    #
    # Other dimension
    #
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      children = ${q(node).context({'dimensions': {'language': ['de']}}).children().get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    children: a1[live,{"language":"de"}],a4-german[live,{"language":"de"}]
    """

    #
    # Show disabled
    #
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      children = ${q(node).context({'invisibleContentShown': true}).children().get()}
      @process.render = Neos.Neos:Test.RenderNodesDataStructure
    }
    """
    Then I expect the following Fusion rendering result:
    """
    children: a1[live,{"language":"en"}],a2-disabled[live,{"language":"en"}]*
    """
