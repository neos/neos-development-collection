@contentrepository @adapters=DoctrineDBAL
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands


  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations       |
      | example    | general, source, spec | spec->source->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content':
      constraints:
        nodeTypes:
          '*': true
          'Neos.ContentRepository.Testing:Document': false
    'Neos.ContentRepository.Testing:DocumentWithTetheredChildNode':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Content'
          constraints:
            nodeTypes:
              '*': true
              'Neos.ContentRepository.Testing:Content': false
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | originDimensionSpacePoint | nodeTypeName                                                 | parentNodeAggregateId  | nodeName        | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough     | {"example": "source"}     | Neos.ContentRepository.Testing:DocumentWithTetheredChildNode | lady-eleonode-rootford | document        | {"tethered": "nodewyn-tetherton"}  |
      | sir-nodeward-nodington-iii | {"example": "source"}     | Neos.ContentRepository.Testing:Document                      | lady-eleonode-rootford | esquire         | {}                                 |
      | anthony-destinode          | {"example": "spec"}       | Neos.ContentRepository.Testing:Document                      | lady-eleonode-rootford | target-document | {}                                 |

  Scenario: Try to move a node in a non-existing workspace:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | workspaceName                | "non-existing"           |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to move a node in a workspace whose content stream is closed:
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  Scenario: Try to move a non-existing node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                 |
      | nodeAggregateId              | "i-do-not-exist"      |
      | dimensionSpacePoint          | {"example": "source"} |
      | relationDistributionStrategy | "scatter"             |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a root node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateId              | "lady-eleonode-rootford" |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to move a node of a tethered node aggregate:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodewyn-tetherton"   |
      | dimensionSpacePoint          | {"example": "source"} |
      | relationDistributionStrategy | "scatter"             |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to move a node in a non-existing dimension space point:
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | dimensionSpacePoint          | {"example": "nope"}      |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to move a node in a dimension space point the aggregate does not cover
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | dimensionSpacePoint          | {"example": "general"}   |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to move a node to a non-existing parent
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                            |
      | nodeAggregateId              | "sir-david-nodenborough"         |
      | newParentNodeAggregateId     | "non-existing-parent-identifier" |
      | relationDistributionStrategy | "scatter"                        |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Using the scatter strategy, try to move a node to a new, existing parent in a dimension space point the new parent does not cover
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | dimensionSpacePoint          | {"example": "source"}    |
      | newParentNodeAggregateId     | "anthony-destinode"      |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet"

  Scenario: Using the gatherSpecializations strategy, try to move a node to a new, existing parent in a dimension space point with a specialization the new parent does not cover
    # reduce coverage of the target
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-david-nodenborough"     |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet"

  Scenario: Using the gatherAll strategy, try to move a node to a new, existing parent in a dimension space point with a generalization the new parent does not cover
    # increase coverage of the source
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "general"}   |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-david-nodenborough"     |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherAll"                  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet"

  Scenario: Try to move a node to a parent that already has a child node of the same name
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | nodeTypeName                            | parentNodeAggregateId      | nodeName |
      | nody-mc-nodeface | {"example": "source"}     | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | document |

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "source"}    |
      | nodeAggregateId              | "nody-mc-nodeface"       |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to move a node to a parent whose node type does not allow child nodes of the node's type
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | nodeTypeName                            | parentNodeAggregateId  | nodeName       |
      | nody-mc-nodeface | {"example": "source"}     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | other-document |

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                 |
      | dimensionSpacePoint          | {"example": "source"} |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | newParentNodeAggregateId     | "nodewyn-tetherton"   |
      | relationDistributionStrategy | "scatter"             |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move a node to a parent whose parent's node type does not allow grand child nodes of the node's type
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | originDimensionSpacePoint | nodeTypeName                           | parentNodeAggregateId  | nodeName |
      | nody-mc-nodeface | {"example": "source"}     | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford | content  |

    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                 |
      | dimensionSpacePoint          | {"example": "source"} |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | newParentNodeAggregateId     | "nodewyn-tetherton"   |
      | relationDistributionStrategy | "scatter"             |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to move existing node to a non-existing preceding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                | Value                    |
      | dimensionSpacePoint                | {"example": "source"}    |
      | nodeAggregateId                    | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "i-do-not-exist"         |
      | relationDistributionStrategy       | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move existing node to a non-existing succeeding sibling
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                                 | Value                    |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | nodeAggregateId                     | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "i-do-not-exist"         |
      | relationDistributionStrategy        | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to move a node to one of its children
    When the command MoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "source"}    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | newParentNodeAggregateId     | "nodewyn-tetherton"      |
      | relationDistributionStrategy | "scatter"                |
    Then the last command should have thrown an exception of type "NodeAggregateIsDescendant"
