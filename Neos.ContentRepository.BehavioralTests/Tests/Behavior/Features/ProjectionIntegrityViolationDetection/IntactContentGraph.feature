@contentrepository @adapters=DoctrineDBAL
Feature: Create an intact content graph and run integrity violation detection

  As a user of the CR I want to be able to get an empty integrity violation detection result on an intact content graph

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | newContentStreamId | "cs-identifier"                        |

  Scenario: Create an intact content graph
    When the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamId     | "cs-identifier"                        |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                       |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"gsw"}                        |
      | coveredDimensionSpacePoints   | [{"language":"gsw"}]                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "esquire"                                 |
      | nodeAggregateClassification   | "tethered"                                |
    And the event NodeReferencesWereSet was published with payload:
      | Key                                      | Value                                                                           |
      | contentStreamId                  | "cs-identifier"                                                                 |
      | sourceNodeAggregateId            | "nody-mc-nodeface"                                                              |
      | affectedSourceOriginDimensionSpacePoints | [{"language":"de"}]                                                             |
      | referenceName                            | "referenceProperty"                                                             |
      | references                               | [{"targetNodeAggregateId":"sir-david-nodenborough", "properties":null}] |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
