@contentrepository
Feature: Run integrity violation detection regarding reference relations

  As a user of the CR I want to know whether there are disconnected reference relations

  Background:
    Given I use the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And the following NodeTypes to define content repository "default":
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        referenceProperty:
          type: reference
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "source-nodandaise"                                      |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "anthony-destinode"                                      |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeAggregateClassification | "regular"                                                |
    And the graph projection is fully up to date

  Scenario: Detach a reference relation from its source
    When the command SetNodeReferences is executed with payload:
      | Key                             | Value                             |
      | contentStreamId                 | "cs-identifier"                   |
      | sourceOriginDimensionSpacePoint | {"language":"de"}                 |
      | sourceNodeAggregateId           | "source-nodandaise"               |
      | referenceName                   | "referenceProperty"               |
      | references                      | [{"target": "anthony-destinode"}] |
    And the graph projection is fully up to date
    And I detach the following reference relation from its source:
      | Key                        | Value               |
      | contentStreamId            | "cs-identifier"     |
      | sourceNodeAggregateId      | "source-nodandaise" |
      | dimensionSpacePoint        | {"language":"gsw"}  |
      | destinationNodeAggregateId | "anthony-destinode" |
      | referenceName              | "referenceProperty" |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585
