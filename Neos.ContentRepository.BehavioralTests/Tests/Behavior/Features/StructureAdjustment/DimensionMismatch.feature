@contentrepository @adapters=DoctrineDBAL
Feature: Dimension mismatch

  Nodes must only cover specializations of their origin.

  - We build this by having a fallback from DE to EN.
  - we create a node in EN (thus it also has an edge in DE)
  - we then turn around the fallback order from EN to DE
  - -> the node still has the "de" incoming edge; and that's not allowed because that's more general than "en"

  Background:
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de | de->en          |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
      | initiatingUserId   | "system-user"        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamId     | "cs-identifier"                          |
      | nodeAggregateId     | "lady-eleonode-rootford"                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"            |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "en"}] |
      | initiatingUserId    | "system-user"                            |
      | nodeAggregateClassification | "root"                                   |
    And the graph projection is fully up to date

  Scenario: Generalization detection
    # Node /document
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "en"}                        |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initiatingUserId      | "user"                                    |
    And the graph projection is fully up to date

    When I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de | en->de          |
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                                | nodeAggregateId |
      | NODE_COVERS_GENERALIZATION_OR_PEERS | sir-david-nodenborough  |
