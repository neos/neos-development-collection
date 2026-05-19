Feature: Move dimension space point from / to the zero dimensional case

  basically "renames" a dimension space point; needed if:
  - the dimension value should be changed: {language: de} -> {language: de_DE}
  - there were no dimensions beforehand, and now there are: {} -> {language: de}
  - ... or the opposite: {language: de} -> {}
  - new dimensions are introduced; so the existing DimensionSpacePoints need an additional value.

  This is the special case handling to / from zero dimensions

  !! Constraint: the Target Dimension Space must be empty.

  Scenario: Move dimension space point from zero- to one-dimensional
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName       | originDimensionSpacePoint |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document       | {}                        |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document | {}                        |

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations |
      | example    | source, peer |                 |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { }
              to: { example: "source" }
    """

    Then I expect the graph projection to consist of exactly 5 nodes

    # the original content stream has not been touched
    When I am in workspace "live"
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph

    # we find the node at the new DimensionSpacePoint, but not at the old one
    When I am in workspace "migration-workspace"
    And I expect a node identified by migration-cs;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by migration-cs;sir-david-nodenborough;{"example": "source"} to exist in the content graph
    And I expect a node identified by migration-cs;nody-mc-nodeface;{"example": "source"} to exist in the content graph

    When I am in dimension space point {}
    Then I expect the subgraph projection to consist of exactly 0 nodes

    When I am in dimension space point {"example": "source"}
    Then I expect the subgraph projection to consist of exactly 3 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{"example": "source"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Move dimension space point from one- to zero-dimensional
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | example    | source, peer |                 |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName       | originDimensionSpacePoint |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document       | {"example": "source"}     |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document | {"example": "source"}     |

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { example: "source" }
              to: { }
    """

    Then I expect the graph projection to consist of exactly 5 nodes

    # the original content stream has not been touched
    When I am in workspace "live"
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"example": "source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example": "source"} to exist in the content graph

    # we find the node at the new DimensionSpacePoint, but not at the old one
    When I am in workspace "migration-workspace"
    And I expect a node identified by migration-cs;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by migration-cs;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by migration-cs;nody-mc-nodeface;{} to exist in the content graph

    When I am in dimension space point {"example": "source"}
    Then I expect the subgraph projection to consist of exactly 0 nodes

    When I am in dimension space point {}
    Then I expect the subgraph projection to consist of exactly 3 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node migration-cs;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node migration-cs;nody-mc-nodeface;{}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
