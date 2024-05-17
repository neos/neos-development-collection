@contentrepository @adapters=DoctrineDBAL
Feature: Dimension mismatch

  Nodes must only cover specializations of their origin.

  - We build this by having a fallback from DE to EN.
  - we create a node in EN (thus it also has an edge in DE)
  - we then turn around the fallback order from EN to DE
  - -> the node still has the "de" incoming edge; and that's not allowed because that's more general than "en"

  Background:
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de | de->en          |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language": "en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | nodeAggregateId     | "lady-eleonode-rootford"                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"            |

  Scenario: Generalization detection
    # Node /document
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "en"}                        |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |

    When I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | en, de | en->de          |
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                                | nodeAggregateId |
      | NODE_COVERS_GENERALIZATION_OR_PEERS | sir-david-nodenborough  |
