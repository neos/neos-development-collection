@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Discard nodes partially with dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | de,gsw,fr,en | gsw->de->en, fr |
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
    And I am in dimension space point {"language": "de"}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Discards nodes partially from user workspace with live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"

    Then I am in dimension space point {"language": "de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                             |
      | sir-david-nodenborough | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                                                |
      | nody-mc-nodeface       | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"} |

    Then I am in dimension space point {"language": "fr"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                       |
      | sir-nodeward-nodington-iii | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bakura   | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"}  |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough     | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {"language":"fr"}         |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"fr"}         |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                                                |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                                     |
      | nodesToDiscard     | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                                                                                                                                                               |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {"language":"fr"}         |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"fr"}         |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"

  Scenario: Discards nodes partially from user workspace with non live base workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review-workspace" |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "review-cs-id"     |

    And I am in workspace "review-workspace"

    Then I am in dimension space point {"language": "de"}
    And  the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues |
      | sir-david-nodenborough | node     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                    |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-workspace"   |
      | baseWorkspaceName  | "review-workspace" |
      | newContentStreamId | "user-cs-id"       |

    And I am in workspace "user-workspace"

    Then I am in dimension space point {"language": "de"}
    And  the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                             |
      | nody-mc-nodeface | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"} |

    Then I am in dimension space point {"language": "gsw"}
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                  |
      | nodeAggregateId           | "nody-mc-nodeface"     |
      | originDimensionSpacePoint | {"language":"gsw"}     |
      | propertyValues            | {"text": "Other text"} |

    And I am in dimension space point {"language": "fr"}
    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                                |
      | sir-nodeward-nodington-iii | esquire  | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a extended text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bakura   | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a extended text about Sir Nodeward Nodington IV"}  |

    Then I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 1       | 1       | 0     | 0       | {"language":"de"}         |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {"language":"gsw"}        |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {"language":"fr"}         |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"fr"}         |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                    |
      | workspaceName      | "user-workspace"                                                                                                         |
      | nodesToDiscard     | ["nody-mc-nodeface"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                                   |

    Then I expect to have the following changes in workspace "review-workspace":
      | nodeAggregateId        | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough | 1       | 1       | 0     | 0       | {"language":"de"}         |
    And I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {"language":"fr"}         |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"fr"}         |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"

  Scenario: Discard nodes partially from user workspace with live base workspace with new generalization
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"

    Then I am in dimension space point {"language": "de"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                                |
      | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                                                                   |
      | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}                    |
      | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a extended text about Sir Nodeward Nodington III"} |
      | sir-nodeward-nodington-iv  | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"}           |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"en"}        |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-david-nodenborough     | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | sir-david-nodenborough     | 1       | 1       | 0     | 0       | {"language":"en"}         |
      | nody-mc-nodeface           | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"de"}         |
      | sir-nodeward-nodington-iii | 1       | 1       | 0     | 0       | {"language":"de"}         |

    When the command DiscardIndividualNodesFromWorkspace is executed with payload:
      | Key                | Value                                                                                                                                                                                                                                                            |
      | workspaceName      | "user-workspace"                                                                                                                                                                                                                                                 |
      | nodesToDiscard     | ["sir-david-nodenborough", "nody-mc-nodeface", "sir-nodeward-nodington-iii"] |
      | newContentStreamId | "user-cs-id-remaining"                                                                                                                                                                                                                                           |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId            | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iv  | 1       | 1       | 0     | 0       | {"language":"de"}         |
    And I expect the ChangeProjection to have no changes in "user-cs-id"
    And I expect to have no changes in workspace "live"
