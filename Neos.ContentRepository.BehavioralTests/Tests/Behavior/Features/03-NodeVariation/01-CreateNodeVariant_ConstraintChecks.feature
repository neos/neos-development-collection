@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node variant

  As a user of the CR I want to create a copy of a node within an aggregate to another dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | market     | DE, CH  | CH->DE          |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                            | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | document | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | {"tethered": "nodewyn-tetherton"}  |
    # We have to add yet another node since we need test cases with a partially covering parent node
    # Node /document/child
      | nody-mc-nodeface       | child    | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | {}                                 |

  Scenario: Try to create a variant in a content stream that does not exist yet
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | contentStreamId | "i-do-not-exist-yet"              |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a variant in a node aggregate that currently does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "i-currently-do-not-exist"        |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant of a root node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "lady-eleonode-rootford"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to create a variant in a tethered node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "nodewyn-tetherton"               |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to create a variant from a source dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                            |
      | nodeAggregateId | "sir-david-nodenborough"         |
      | sourceOrigin    | {"undeclared":"undefined"}       |
      | targetOrigin    | {"market":"DE", "language":"de"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant from a source dimension space point that the node aggregate does not occupy
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a variant to a target dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"undeclared":"undefined"}        |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant to a target dimension space point that the node aggregate already occupies
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"DE", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a variant to a target dimension space point that neither the node aggregate nor its parent in the source dimension point cover
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "nody-mc-nodeface"                |
      | sourceOrigin    | {"market":"DE", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
