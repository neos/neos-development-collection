@contentrepository
Feature: Copy nodes constraint checks

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
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

    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                            |
      | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |
      | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |

  Scenario: Coping fails if the source node does not exist
    When copy nodes recursively is executed with payload:
      | Key                         | Value               |
      | sourceDimensionSpacePoint   | {}                  |
      | sourceNodeAggregateId       | "not-existing"      |
      | targetDimensionSpacePoint   | {}                  |
      | targetParentNodeAggregateId | "nody-mc-nodeface"  |

    Then an exception of type NodeAggregateCurrentlyDoesNotExist should be thrown with code 1732006772

  Scenario: Coping fails if the target node does not exist
    When copy nodes recursively is executed with payload:
      | Key                         | Value               |
      | sourceDimensionSpacePoint   | {}                  |
      | sourceNodeAggregateId       | "nody-mc-nodeface"  |
      | targetDimensionSpacePoint   | {}                  |
      | targetParentNodeAggregateId | "not-existing"      |

    Then an exception of type NodeAggregateCurrentlyDoesNotExist should be thrown with code 1732006769

  Scenario: Coping fails if the source node is a root
    When copy nodes recursively is executed with payload:
      | Key                         | Value                    |
      | sourceDimensionSpacePoint   | {}                       |
      | sourceNodeAggregateId       | "lady-eleonode-rootford" |
      | targetDimensionSpacePoint   | {}                       |
      | targetParentNodeAggregateId | "nody-mc-nodeface"       |

    Then an exception of type NodeTypeIsOfTypeRoot should be thrown with code 1541765806
