Feature: Update root node aggregate dimension space point

  These are the constraint check tests to prevent damage to the content repository state

  Background:
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | fr, de |                 |
    And using the following node types:
    """yaml
    Neos.ContentRepository.Testing:Document: {}
    Neos.ContentRepository.Testing:Root:
      superTypes:
        Neos.ContentRepository:Root: true
    Neos.ContentRepository.Testing:RootWithTethered:
      superTypes:
        Neos.ContentRepository:Root: true
      childNodes:
        tethered:
          type: "Neos.ContentRepository.Testing:Document"
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Constraint updating dimensions without changes
    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then the last command should have thrown an exception of type "RuntimeException" with message:
    """
    The root node aggregate lady-eleonode-rootford covers already all allowed dimensions: [{"language":"fr"},{"language":"de"}].
    """

  Scenario: Constraint updating new fallbacks for existing dimensions is not allowed (new specialisations cannot be inserted via update)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | de, fr, ch | ch -> de        |

    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then the last command should have thrown an exception of type "RuntimeException" with message:
    """
    Cannot add fallback dimensions via update root node aggregate because node lady-eleonode-rootford already covers generalisations [{"language":"de"}]. Use AddDimensionShineThrough instead.
    """

  Scenario: Error case - there are changes in another workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | workspaceName             | "user-test"                               |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | fr     |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then the last command should have thrown an exception of type "WorkspaceContainsPublishableChanges" with message:
    """
    The following workspaces still contain changes: user-test
    """

  Scenario: Error case - adjusting workspace that is non-root or not immediately based on root
  This limitation is required as we validate that all workspaces except the current on is empty.
  For publishing we store the originally attempted workspace in $initialWorkspaceName as during the
  publication this workspace is allowed to contain changes. Allowing to publish adjustments through multiple workspace
  complicates things and is not desired, as they are rare fundamental changes that should be run on root or in a migration
  (sandbox) workspace which is published to root.

    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "shared"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "shared-cs-identifier" |

    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "shared"             |
      | newContentStreamId | "user-cs-identifier" |

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | fr     |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | workspaceName   | "user-test"              |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then the last command should have thrown an exception of type "InvalidDimensionAdjustmentTargetWorkspace"

  Scenario: Removing a dimension and attempt to promoting its fallback to a root generalisation with conflicting tethered node
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | fr, de, gsw | gsw->de         |

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                             |
      | nodeAggregateId                    | "root-zwo"                                        |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:RootWithTethered" |
      | originDimensionSpacePoint          | {}                                                |
      | tetheredDescendantNodeAggregateIds | {"tethered": "nodimus-tetherton"}                 |

    Then I expect the node aggregate "root-zwo" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"gsw"}]

    Then I expect the node aggregate "nodimus-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"},{"language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"gsw"}]

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values  | Generalizations |
      | language   | fr, gsw |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value      |
      | nodeAggregateId | "root-zwo" |

    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint" with message:
    """
    Descendant Node nodimus-tetherton in dimensions [{"language":"gsw"}] must not fallback to dimension {"language":"de"} which will be removed.
    """

  Scenario: Removing a dimension and attempt to promoting its fallback to a root generalisation with conflicting child node
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | fr, de, gsw | gsw->de         |

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "root-three"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Root" |
      | originDimensionSpacePoint | {}                                    |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "root-three"                              |

    Then I expect the node aggregate "root-three" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"gsw"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"}]

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values  | Generalizations |
      | language   | fr, gsw |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value        |
      | nodeAggregateId | "root-three" |

    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint" with message:
    """
    Descendant Node sir-david-nodenborough in dimensions [{"language":"gsw"}] must not fallback to dimension {"language":"de"} which will be removed.
    """

  Scenario: Error case - there's already an edge in the target dimension in another workspace, e.g. by executing the same command
    Given the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "root-three"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Root" |
      | originDimensionSpacePoint | {}                                    |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                     |
      | baseWorkspaceName  | "live"                    |
      | workspaceName      | "migration-workspace"     |
      | newContentStreamId | "migration-cs-identifier" |
    And I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | fr, de, en |                 |
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value        |
      | nodeAggregateId | "root-three" |
      | workspaceName   | "live"       |

    When the command UpdateRootNodeAggregateDimensions is executed with payload and exceptions are caught:
      | Key             | Value                 |
      | nodeAggregateId | "root-three"          |
      | workspaceName   | "migration-workspace" |
    Then the last command should have thrown an exception of type "DimensionSpacePointAlreadyExists"
