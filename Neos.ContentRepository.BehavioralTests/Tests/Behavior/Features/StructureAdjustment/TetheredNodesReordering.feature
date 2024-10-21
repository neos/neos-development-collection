@contentrepository @adapters=DoctrineDBAL
Feature: Tethered Nodes Reordering Structure changes

  As a user of the CR I want to be able to detect wrongly ordered tethered nodes, and fix them.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'other-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'third-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    When I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                | Value                                                                                                                                      |
      | nodeAggregateId                    | "sir-david-nodenborough"                                                                                                                   |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Document"                                                                                                  |
      | originDimensionSpacePoint          | {}                                                                                                                                         |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                                                                                                                   |
      | nodeName                           | "document"                                                                                                                                 |
      | tetheredDescendantNodeAggregateIds | {"tethered-node": "tethered-node-agg", "other-tethered-node": "other-tethered-node-agg", "third-tethered-node": "third-tethered-node-agg"} |

    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

    And I get the node at path "document/tethered-node"
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;other-tethered-node-agg;{} |
      | cs-identifier;third-tethered-node-agg;{} |

  Scenario: re-ordering the tethered child nodes brings up wrongly sorted tethered nodes
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'other-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
          position: start
        'third-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                          | nodeAggregateId        |
      | TETHERED_NODE_WRONGLY_ORDERED | sir-david-nodenborough |
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

    When I am in workspace "live" and dimension space point {}
    And I get the node at path "document/tethered-node"
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;other-tethered-node-agg;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;third-tethered-node-agg;{} |
