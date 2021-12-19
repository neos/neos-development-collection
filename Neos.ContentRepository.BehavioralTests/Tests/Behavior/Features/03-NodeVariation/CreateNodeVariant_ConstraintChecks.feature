@fixtures
Feature: Create node variant

  As a user of the CR I want to create a copy of a node within an aggregate to another dimension space point.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "user-id"            |
    And I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeName | parentNodeAggregateIdentifier | nodeTypeName                            | tetheredDescendantNodeAggregateIdentifiers |
      | sir-david-nodenborough  | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document | {"tethered": "nodewyn-tetherton"}          |
    # We have to add yet another node since we need test cases with a partially covering parent node
    # Node /document/child
      | nody-mc-nodeface        | child    | sir-david-nodenborough        | Neos.ContentRepository.Testing:Document | {}                                         |

  Scenario: Try to create a variant in a content stream that does not exist yet
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | contentStreamIdentifier | "i-do-not-exist-yet"              |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a variant in a node aggregate that currently does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "i-currently-do-not-exist"        |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant of a root node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to create a variant in a tethered node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "nodewyn-tetherton"               |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to create a variant from a source dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                            |
      | nodeAggregateIdentifier | "sir-david-nodenborough"         |
      | sourceOrigin            | {"undeclared":"undefined"}       |
      | targetOrigin            | {"market":"DE", "language":"de"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant from a source dimension space point that the node aggregate does not occupy
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a variant to a target dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"undeclared":"undefined"}        |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant to a target dimension space point that the node aggregate already occupies
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"DE", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a variant to a target dimension space point that neither the node aggregate nor its parent in the source dimension point cover
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                     | Value                             |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                |
      | sourceOrigin            | {"market":"DE", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
