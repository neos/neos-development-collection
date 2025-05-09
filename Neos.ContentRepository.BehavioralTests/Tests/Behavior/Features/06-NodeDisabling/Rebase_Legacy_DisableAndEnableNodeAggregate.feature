@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Rebase disable a node aggregate

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
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
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "succeeding-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Disable node and rebase
    And I am in workspace "user-test" and dimension space point {}

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 1 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier"     |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                    |
      | workspaceName               | "user-test"              |
      | rebasedContentStreamId      | "user-cs-identifier-new" |
      | rebaseErrorHandlingStrategy | "force"                  |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:user-cs-identifier-new"
    And event at index 3 is of type "ContentStreamWasReopened" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier-new" |
    And event at index 2 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier-new" |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |


  Scenario: Enabled node and rebase
    And I am in workspace "user-test" and dimension space point {}

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "succeeding-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 1 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier"     |
      | nodeAggregateId              | "succeeding-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                    |
      | workspaceName               | "user-test"              |
      | rebasedContentStreamId      | "user-cs-identifier-new" |
      | rebaseErrorHandlingStrategy | "force"                  |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:user-cs-identifier-new"
    And event at index 3 is of type "ContentStreamWasReopened" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier-new" |
    And event at index 2 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "user-cs-identifier-new" |
      | nodeAggregateId              | "succeeding-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |

