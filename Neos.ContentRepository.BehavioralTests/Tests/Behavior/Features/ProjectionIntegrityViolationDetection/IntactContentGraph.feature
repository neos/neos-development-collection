Feature: Create an intact content graph and run integrity violation detection

  As a user of the CR I want to be able to get an empty integrity violation detection result on an intact content graph

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      references:
        myReferences: {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |

  Scenario: Create an intact content graph
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                  |
      | workspaceName               | "live"                                 |
      | nodeAggregateId             | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "sir-david-nodenborough"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeName                    | "document"                                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "nody-mc-nodeface"                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"}]                       |
      | parentNodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeName                    | "child-document"                          |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"gsw"}                        |
      | coveredDimensionSpacePoints | [{"language":"gsw"}]                      |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeName                    | "esquire"                                 |
      | nodeAggregateClassification | "tethered"                                |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "live"                                                                                   |
      | sourceNodeAggregateId           | "nody-mc-nodeface"                                                                       |
      | sourceOriginDimensionSpacePoint | {"language":"de"}                                                                        |
      | references                      | [{"referenceName": "myReferences", "references": [{"target":"sir-david-nodenborough"}]}] |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
