@fixtures
Feature: Create node variant

  As a user of the CR I want to create a copy of a node within an aggregate to another dimension space point.

  #todo: test exception to be thrown when trying to directly create variants of tethered nodes

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:Document': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | currentContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "lady-eleonode-rootford"                                                                                                                |
      | nodeTypeName                  | "Neos.ContentRepository:Root"                                                                                                           |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                                                                                                  |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                 |
      | contentStreamIdentifier       | "cs-identifier"                                                       |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                              |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                     |
      | originDimensionSpacePoint     | {"market":"DE", "language":"gsw"}                                     |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                              |
      | nodeName                      | "document"                                                            |
    # We have to add yet another node since root nodes are visible in all dimension space points
    # and we need a test case with a partially visible parent node
    # Node /document/child
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                 |
      | contentStreamIdentifier       | "cs-identifier"                                                       |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                    |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                     |
      | originDimensionSpacePoint     | {"market":"DE", "language":"gsw"}                                     |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                              |
      | nodeName                      | "child"                                                               |
    And the graph projection is fully up to date

  Scenario: Try to create a variant in a content stream that does not exist yet
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "i-do-not-exist-yet"              |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a variant in a node aggregate that currently does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "i-currently-do-not-exist"        |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant in a root node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "lady-eleonode-rootford"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeTypeIsOfTypeRoot"

  Scenario: Try to create a variant from a source dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                            |
      | contentStreamIdentifier   | "cs-identifier"                  |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"         |
      | sourceDimensionSpacePoint | {"undeclared":"undefined"}       |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant to a target dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"undeclared":"undefined"}        |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant from a source dimension space point that the node aggregate does not occupy
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a variant to a target dimension space point that the node aggregate already occupies
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a variant to a target dimension space point that the node aggregate's parent in the source dimension point does not cover
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"