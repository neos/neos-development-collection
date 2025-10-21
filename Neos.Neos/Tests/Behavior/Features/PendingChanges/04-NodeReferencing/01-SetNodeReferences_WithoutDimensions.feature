@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Node referencing without dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        text:
          type: string
      references:
       singleReference:
         constraints:
           maxItems: 1
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |

    And I am in workspace "live"
    And I am in dimension space point {}

    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId           | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                      | references                                                                                      |
      | sir-david-nodenborough    | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                                                         | []                                                                                              |
      | nody-mc-nodeface          | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}          | [{"referenceName": "singleReference", "references": [{"target": "sir-nodeward-nodington-iv"}]}] |
      | sir-nodeward-nodington-iv | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"} | []                                                                                              |


    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And I am in dimension space point {}

  Scenario: Set a new node reference
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                           |
      | sourceNodeAggregateId | "sir-david-nodenborough"                                                                        |
      | references            | [{"referenceName": "singleReference", "references": [{"target": "sir-nodeward-nodington-iv"}]}] |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 0       | 1       | 0     | 0       | {}                        |
    And I expect to have no changes in workspace "live"

  Scenario: Remove an existing reference from node
    When the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                    |
      | sourceNodeAggregateId | "nody-mc-nodeface"                                       |
      | references            | [{"referenceName": "singleReference", "references": []}] |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 1       | 0     | 0       | {}                        |
    And I expect to have no changes in workspace "live"
