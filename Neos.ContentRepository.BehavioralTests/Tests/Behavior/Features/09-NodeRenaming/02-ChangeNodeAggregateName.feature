@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate name

  As a user of the CR I want to change the name of a node aggregate

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node': {}
    'Neos.ContentRepository.Testing:NodeWithTetheredChildren':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Node'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example":"source"}

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                                            | originDimensionSpacePoint | parentNodeAggregateId  | nodeName            | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Node                     | {"example":"general"}     | lady-eleonode-rootford | parent-document     | {}                                 |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:NodeWithTetheredChildren | {"example":"source"}      | sir-david-nodenborough | document            | {"tethered": "nodimus-prime"}      |
      | nodimus-mediocre       | Neos.ContentRepository.Testing:Node                     | {"example":"source"}      | nodimus-prime          | grandchild-document | {}                                 |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    # leave spec as a virtual variant
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"peer"}    |

  Scenario: Rename a child node aggregate with descendants
    When the command ChangeNodeAggregateName is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "renamed-document" |

    Then I expect exactly 11 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeAggregateNameWasChanged" with payload:
      | Key             | Expected           |
      | contentStreamId | "cs-identifier"    |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "renamed-document" |

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be named "renamed-document"

    And I expect the graph projection to consist of exactly 9 nodes

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"general"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"source"}

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"source"}

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"peer"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to no node

  Scenario: Rename a scattered node aggregate
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                     |
      | nodeAggregateId                     | "nody-mc-nodeface"        |
      | dimensionSpacePoint                 | {"example": "peer"}       |
      | newParentNodeAggregateId            | "lady-eleonode-rootford"  |
      | relationDistributionStrategy        | "scatter"                 |

    When the command ChangeNodeAggregateName is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "renamed-document" |

    Then I expect exactly 12 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 11 is of type "NodeAggregateNameWasChanged" with payload:
      | Key             | Expected           |
      | contentStreamId | "cs-identifier"    |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "renamed-document" |

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be named "renamed-document"

    And I expect the graph projection to consist of exactly 9 nodes

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"general"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to no node

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"source"}

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "parent-document/renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"source"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/renamed-document/tethered/grandchild-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"source"}

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "renamed-document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"peer"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "renamed-document/tethered" to lead to node cs-identifier;nodimus-prime;{"example":"peer"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "renamed-document/tethered/grandchild-document" to lead to no node
