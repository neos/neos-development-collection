@contentrepository @adapters=DoctrineDBAL
Feature: Move a node aggregate considering disable state but without content dimensions

  As a user of the CR I want to move a node that
  - is disabled by one of its ancestors
  - disables itself
  - disables any of its descendants
  - is enabled

  to a new parent that
  - is enabled
  - disables itself
  - is disabled by one of its ancestors

  These are the test cases without content dimensions being involved

  Background:
    Given using no content dimensions
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
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                  |
      | contentStreamId     | "cs-identifier"                        |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "esquire"                                 |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nodimus-prime"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "sir-nodeward-nodington-iii"              |
      | nodeName                      | "esquire-child"                           |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Move a node disabled by one of its ancestors to a new parent that is enabled
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{}

  Scenario: Move a node disabled by itself to a new parent that is enabled
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value              |
      | contentStreamId      | "cs-identifier"    |
      | nodeAggregateId      | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [{}]               |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document" to lead to no node

  Scenario: Move a node that disables one of its descendants to a new parent that is enabled
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-david-nodenborough"     |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document/child-document" to lead to no node

  Scenario: Move a node that is disabled by one of its ancestors to a new parent that disables itself
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document" to lead to no node

  Scenario: Move a node that is disabled by itself to a new parent that disables itself
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-david-nodenborough"     |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to no node

  Scenario: Move a node that is enabled to a new parent that disables itself
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-david-nodenborough"     |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to no node

  Scenario: Move a node that disables any of its descendants to a new parent that disables itself
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamId                     | "cs-identifier"              |
      | nodeAggregateId                     | "sir-david-nodenborough"     |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | null                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document/child-document" to lead to no node

  Scenario: Move a node that is disabled by one of its ancestors to a new parent that is disabled by one of its ancestors
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value              |
      | contentStreamId                     | "cs-identifier"    |
      | nodeAggregateId                     | "nody-mc-nodeface" |
      | dimensionSpacePoint                         | {}                 |
      | newParentNodeAggregateId            | "nodimus-prime"    |
      | newSucceedingSiblingNodeAggregateId | null               |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/child-document" to lead to no node

  Scenario: Move a node that is disabled by itself to a new parent that is disabled by one of its ancestors
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamId                     | "cs-identifier"          |
      | nodeAggregateId                     | "sir-david-nodenborough" |
      | dimensionSpacePoint                         | {}                       |
      | newParentNodeAggregateId            | "nodimus-prime"          |
      | newSucceedingSiblingNodeAggregateId | null                     |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/esquire-child/document" to lead to no node

  Scenario: Move a node that disables any of its descendants to a new parent that is disabled by one of its ancestors
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId      | "cs-identifier"          |
      | nodeAggregateId      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamId                     | "cs-identifier"          |
      | nodeAggregateId                     | "sir-david-nodenborough" |
      | dimensionSpacePoint                         | {}                       |
      | newParentNodeAggregateId            | "nodimus-prime"          |
      | newSucceedingSiblingNodeAggregateId | null                     |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/esquire-child/document" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document/child-document" to lead to no node

  Scenario: Move a node that is enabled to a new parent that is disabled by one of its ancestors
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                        |
      | contentStreamId      | "cs-identifier"              |
      | nodeAggregateId      | "sir-nodeward-nodington-iii" |
      | affectedDimensionSpacePoints | [{}]                         |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamId                     | "cs-identifier"          |
      | nodeAggregateId                     | "sir-david-nodenborough" |
      | dimensionSpacePoint                         | {}                       |
      | newParentNodeAggregateId            | "nodimus-prime"          |
      | newSucceedingSiblingNodeAggregateId | null                     |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/esquire-child/document" to lead to no node
