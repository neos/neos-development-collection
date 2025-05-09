@contentrepository @adapters=DoctrineDBAL
Feature: Missing Tethered Nodes integrity violations

  As a user of the CR I want to be able to detect and fix tethered nodes that are missing

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        'originally-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      properties:
        foo:
          type: "string"
          defaultValue: "my default applied"
      childNodes:
        'tethered-leaf':
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                                                                     |
      | nodeAggregateId                    | "lady-eleonode-rootford"                                                                                                  |
      | nodeTypeName                       | "Neos.ContentRepository:Root"                                                                                             |
      | tetheredDescendantNodeAggregateIds | {"originally-tethered-node": "originode-tetherton", "originally-tethered-node/tethered-leaf": "originode-tetherton-leaf"} |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                                      |
      | nodeAggregateId                    | "sir-david-nodenborough"                                                                   |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Document"                                                  |
      | originDimensionSpacePoint          | {"example": "source"}                                                                      |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                                                                   |
      | nodeName                           | "document"                                                                                 |
      | tetheredDescendantNodeAggregateIds | {"tethered-node": "nodewyn-tetherton", "tethered-node/tethered-leaf": "nodimer-tetherton"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example":"source"}     |
      | targetOrigin    | {"example":"peer"}       |

    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Tethered"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:TetheredLeaf"

  Scenario: Adjusting the schema adding a new tethered node leads to a MissingTetheredNode integrity violation
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        'originally-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'new-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      properties:
        foo:
          type: "string"
          defaultValue: "my default applied"
      childNodes:
        'tethered-leaf':
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    """
    And I expect the following structure adjustments for type "Neos.ContentRepository:Root":
      | Type                  | nodeAggregateId        |
      | TETHERED_NODE_MISSING | lady-eleonode-rootford |
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateId        | dimensionSpacePoint  |
      | TETHERED_NODE_MISSING | sir-david-nodenborough | {"example":"source"} |
      | TETHERED_NODE_MISSING | sir-david-nodenborough | {"example":"peer"}   |

    When I adjust the node structure for node type "Neos.ContentRepository:Root"
    Then I expect exactly 12 events to be published on stream "ContentStream:cs-identifier"
    And I expect the graph projection to consist of exactly 11 nodes

    And I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    And I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateId        | dimensionSpacePoint  |
      | TETHERED_NODE_MISSING | sir-david-nodenborough | {"example":"source"} |
      | TETHERED_NODE_MISSING | sir-david-nodenborough | {"example":"peer"}   |
    And I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Tethered"
    And I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:TetheredLeaf"

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And I expect the graph projection to consist of exactly 15 nodes

    And I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    And I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    And I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Tethered"
    And I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:TetheredLeaf"
