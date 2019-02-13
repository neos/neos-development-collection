@fixtures
Feature: Remove NodeAggregate

  As a user of the CR I want to be able to remove a NodeAggregate completely.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:Document': []
    """
    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "live-cs-identifier"                   |
      | nodeIdentifier           | "rn-identifier"                        |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
    # We have to add another node since root nodes are in all dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "live-cs-identifier"                   |
      | nodeAggregateIdentifier       | "doc-agg-identifier"                   |
      | nodeTypeName                  | "Neos.ContentRepository:Document"      |
      | dimensionSpacePoint           | {"language":"de"}                      |
      | visibleInDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeIdentifier                | "doc-identifier-de"                    |
      | parentNodeIdentifier          | "rn-identifier"                        |
      | nodeName                      | "document"                             |
      | propertyDefaultValuesAndTypes | {}                                     |
    # We also want to add a child node to make sure it is correctly removed when the parent is removed
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "live-cs-identifier"                   |
      | nodeAggregateIdentifier       | "cdoc-agg-identifier"                  |
      | nodeTypeName                  | "Neos.ContentRepository:Document"      |
      | dimensionSpacePoint           | {"language":"de"}                      |
      | visibleInDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeIdentifier                | "cdoc-identifier-de"                   |
      | parentNodeIdentifier          | "doc-identifier-de"                    |
      | nodeName                      | "child-document"                       |
      | propertyDefaultValuesAndTypes | {}                                     |

    And the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                |
      | contentStreamIdentifier   | "live-cs-identifier" |
      | nodeAggregateIdentifier   | "doc-agg-identifier" |
      | sourceDimensionSpacePoint | {"language":"de"}    |
      | targetDimensionSpacePoint | {"language":"gsw"}   |
      | specializationIdentifier  | "doc-identifier-gsw" |
    And the graph projection is fully up to date

  ########################
  # Section: EXTRA testcases
  ########################
  Scenario: (Exception) Trying to remove a non existing nodeAggregate should fail with an exception
    When the command RemoveNodeAggregate was published with payload and exceptions are caught:
      | Key                     | Value                         |
      | contentStreamIdentifier | "live-cs-identifier"          |
      | nodeAggregateIdentifier | "non-existing-agg-identifier" |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: In LIVE workspace, removing a NodeAggregate removes all nodes completely

    When the command RemoveNodeAggregate was published with payload:
      | Key                     | Value                |
      | contentStreamIdentifier | "live-cs-identifier" |
      | nodeAggregateIdentifier | "doc-agg-identifier" |
    And the graph projection is fully up to date

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "doc-identifier-gsw" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

  Scenario: In USER workspace, removing a NodeAggregate removes all nodes completely; leaving the live workspace untouched

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "live-cs-identifier" |
    And the graph projection is fully up to date

    When the command RemoveNodeAggregate was published with payload:
      | Key                     | Value                |
      | contentStreamIdentifier | "user-cs-identifier" |
      | nodeAggregateIdentifier | "doc-agg-identifier" |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-gsw" not to exist in the graph projection
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "doc-identifier-gsw" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection
    Then I expect a node "gcdoc-identifier-de" not to exist in the graph projection

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    Then I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect the path "document" to lead to the node "doc-identifier-gsw"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    Then I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"
