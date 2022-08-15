@contentrepository
Feature: Run integrity violation detection regarding reference relations

  As a user of the CR I want to know whether there are disconnected reference relations

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        referenceProperty:
          type: reference
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamIdentifier     | "cs-identifier"                                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                   |
      | nodeAggregateClassification | "root"                                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                          |
      | nodeAggregateIdentifier       | "source-nodandaise"                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint     | {"language":"de"}                                        |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                 |
      | nodeAggregateClassification   | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                          |
      | nodeAggregateIdentifier       | "anthony-destinode"                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint     | {"language":"de"}                                        |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                 |
      | nodeAggregateClassification   | "regular"                                                |
    And the graph projection is fully up to date

  Scenario: Detach a reference relation from its source
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                                  |
      | contentStreamIdentifier             | "cs-identifier"                        |
      | sourceOriginDimensionSpacePoint     | {"language":"de"}                      |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"                    |
      | referenceName                       | "referenceProperty"                    |
      | references                          | [{"target": "anthony-destinode"}]      |
      | initiatingUserIdentifier            | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date
    And I detach the following reference relation from its source:
      | Key                                | Value               |
      | contentStreamIdentifier            | "cs-identifier"     |
      | sourceNodeAggregateIdentifier      | "source-nodandaise" |
      | dimensionSpacePoint                | {"language":"gsw"}  |
      | destinationNodeAggregateIdentifier | "anthony-destinode" |
      | referenceName                      | "referenceProperty" |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585
