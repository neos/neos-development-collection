@contentrepository @adapters=DoctrineDBAL
Feature: Additional constraint checks after move node capabilities are introduced

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeTypeName                            | parentNodeAggregateId  | nodeName              |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | parent-document       |
      | lady-abigail-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | other-parent-document |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | esquire               |
      | general-nodesworth         | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | general-document      |
      | bustling-mc-nodeface       | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document              |

  Scenario: Scatter a named node aggregate among different parents, then try to create a new node with the same name under one of the new parents
    Given the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "bustling-mc-nodeface"       |
      | dimensionSpacePoint          | {"example": "spec"}          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "scatter"                    |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "bustling-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "peer"}         |
      | newParentNodeAggregateId     | "lady-abigail-nodenborough" |
      | relationDistributionStrategy | "scatter"                   |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "bustling-mc-nodeface" |
      | dimensionSpacePoint          | {"example": "general"} |
      | newParentNodeAggregateId     | "general-nodesworth"   |
      | relationDistributionStrategy | "scatter"              |

    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                     |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "sir-nodeward-nodington-iii"              |
      | nodeName              | "document"                                |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                     |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "lady-abigail-nodenborough"               |
      | nodeName              | "document"                                |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                     |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "general-nodesworth"                      |
      | nodeName              | "document"                                |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"


