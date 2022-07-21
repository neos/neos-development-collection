@fixtures @adapters=DoctrineDBAL
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamIdentifier     | "cs-identifier"                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"            |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "en"}] |
      | initiatingUserIdentifier    | "system-user"                            |
      | nodeAggregateClassification | "root"                                   |
    And the graph projection is fully up to date

  Scenario: Generalization detection
    # Node /document
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "en"}                        |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initiatingUserIdentifier      | "user"                                    |
    And the graph projection is fully up to date

    When I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de | en->de          |
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                                | nodeAggregateIdentifier |
      | NODE_COVERS_GENERALIZATION_OR_PEERS | sir-david-nodenborough  |
