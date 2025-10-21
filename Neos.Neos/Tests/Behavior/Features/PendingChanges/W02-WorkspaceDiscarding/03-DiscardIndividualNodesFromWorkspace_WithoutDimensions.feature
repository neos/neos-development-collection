@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Discard nodes partially without dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        text:
          type: string
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

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues |
      | sir-david-nodenborough | node     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                    |

  Scenario: Discards nodes partially from user workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                       |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}           |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington III"} |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                             |
      | workspaceName      | "user-workspace"                                                                                                  |
      | nodesToDiscard     | ["sir-nodeward-nodington-iii"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                            |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"

  Scenario: Discards multiple nodes partially from user workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                       |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}           |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"}  |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                            |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                 |
      | nodesToDiscard     | ["sir-nodeward-nodington-iii", "sir-nodeward-nodington-iv"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                                                                                                                                           |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"

  Scenario: Discards nodes partially from user workspace with a non-live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review-workspace" |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "review-cs-id"     |

    And I am in workspace "review-workspace"
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                             |
      | nody-mc-nodeface | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"} |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-workspace"   |
      | baseWorkspaceName  | "review-workspace" |
      | newContentStreamId | "user-cs-id"       |

    And I am in workspace "user-workspace"

    And I am in dimension space point {}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                       |
      | sir-nodeward-nodington-iii | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bukara   | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"}  |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {}                        |
    And I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                            |
      | workspaceName      | "user-workspace"                                                                                                 |
      | nodesToDiscard     | ["sir-nodeward-nodington-iv"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                           |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |
    And I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"

  Scenario: Discards multiple nodes partially from user workspace with a non-live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review-workspace" |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "review-cs-id"     |

    And I am in workspace "review-workspace"
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                             |
      | nody-mc-nodeface | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"} |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-workspace"   |
      | baseWorkspaceName  | "review-workspace" |
      | newContentStreamId | "user-cs-id"       |

    And I am in workspace "user-workspace"

    And I am in dimension space point {}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName  | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                       |
      | sir-nodeward-nodington-iii | esquire   | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bukara    | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"}  |
      | sir-nodeward-nodington-v   | tinquarto | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington V"}   |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {}                        |
      | sir-nodeward-nodington-v   | 1       | 1       | 0     | 0       | {}                        |
    And I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                          |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                               |
      | nodesToDiscard     | ["sir-nodeward-nodington-iv", "sir-nodeward-nodington-v"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                                                                                                                                         |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {}                        |
    And I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 1       | 1       | 0     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"
