@contentrepository @adapters=DoctrineDBAL
Feature: Workspace status
  The workspace status signals if the workspace is UP_TO_DATE or OUTDATED
  All depending workspaces are considered OUTDATED if changes are made or published into a workspace

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId  | nodeName |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford | child    |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "live"               |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Original"} |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-ws-one"      |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "user-cs-one"      |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "shared"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "shared-cs-identifier" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-ws-two"      |
      | baseWorkspaceName  | "shared"           |
      | newContentStreamId | "user-cs-two"      |

  Scenario: Changes to the root workspace render dependents outdated
    Then workspaces live,shared,user-ws-one,user-ws-two have status UP_TO_DATE

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "live"               |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Revision"} |

    Then workspace live has status UP_TO_DATE
    Then workspaces shared,user-ws-one have status OUTDATED
    # the others users workspace is not outdated because it depends on shared
    Then workspace user-ws-two has status UP_TO_DATE

  Scenario: Publishing to the root workspace render dependents outdated
    Then workspaces live,shared,user-ws-one,user-ws-two have status UP_TO_DATE

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-ws-one"        |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Revision"} |

    Then workspaces live,shared,user-ws-one,user-ws-two have status UP_TO_DATE

    And the command PublishWorkspace is executed with payload:
      | Key           | Value         |
      | workspaceName | "user-ws-one" |

    Then workspaces live,user-ws-one have status UP_TO_DATE
    Then workspace shared has status OUTDATED
    # the others users workspace is not outdated because it depends on shared
    Then workspace user-ws-two has status UP_TO_DATE

    #
    # Rebasing to get everything up to date
    #

    When the command RebaseWorkspace is executed with payload:
      | Key                    | Value                 |
      | workspaceName          | "shared"              |
      | rebasedContentStreamId | "shared-rebased"      |

    Then workspaces live,shared,user-ws-one have status UP_TO_DATE
    Then workspace user-ws-two has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                    | Value                 |
      | workspaceName          | "user-ws-two"         |
      | rebasedContentStreamId | "user-ws-two-rebased" |

    Then workspaces live,shared,user-ws-one,user-ws-two have status UP_TO_DATE
