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
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "live-cs-identifier"                   |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "live-cs-identifier"                   |
      | nodeAggregateIdentifier  | "lady-eleonode-nodesworth"             |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes are in all dimension space points and thus cannot be varied
    # Node /document
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "live-cs-identifier"                      |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                |
      | nodeName                      | "document"                                |
    And the graph projection is fully up to date
    # We also want to add a child node to make sure it is correctly removed when the parent is removed
    # Node /document/child-document
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "live-cs-identifier"                      |
      | nodeAggregateIdentifier       | "nodimus-prime"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "nody-mc-nodeface"                        |
      | nodeName                      | "child-document"                          |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                |
      | contentStreamIdentifier | "live-cs-identifier" |
      | nodeAggregateIdentifier | "nody-mc-nodeface"   |
      | sourceOrigin            | {"language":"de"}    |
      | targetOrigin            | {"language":"gsw"}   |
    And the graph projection is fully up to date

  ########################
  # Section: EXTRA testcases
  ########################
  Scenario: In LIVE workspace, removing a NodeAggregate removes all nodes completely
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | contentStreamIdentifier      | "live-cs-identifier" |
      | nodeAggregateIdentifier      | "nody-mc-nodeface"   |
      | nodeVariantSelectionStrategy | "allVariants"        |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | initiatingUserIdentifier     | "user"               |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 1 node
    And I expect a node identified by live-cs-identifier;lady-eleonode-nodesworth;{} to exist in the content graph

    When I am in content stream "live-cs-identifier" and dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node live-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}

    When I am in content stream "live-cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node live-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}

  Scenario: In USER workspace, removing a NodeAggregate removes all nodes completely; leaving the live workspace untouched

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                        |
      | contentStreamIdentifier       | "user-cs-identifier"         |
      | sourceContentStreamIdentifier | "live-cs-identifier"         |
      | initiatingUserIdentifier      | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | contentStreamIdentifier      | "user-cs-identifier" |
      | nodeAggregateIdentifier      | "nody-mc-nodeface"   |
      | nodeVariantSelectionStrategy | "allVariants"        |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | initiatingUserIdentifier     | "user"               |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by live-cs-identifier;lady-eleonode-nodesworth;{} to exist in the content graph
    And I expect a node identified by live-cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect a node identified by live-cs-identifier;nody-mc-nodeface;{"language":"gsw"} to exist in the content graph
    And I expect a node identified by live-cs-identifier;nodimus-prime;{"language":"de"} to exist in the content graph

    When I am in content stream "user-cs-identifier" and dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node user-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}

    When I am in content stream "user-cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node user-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 3 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node live-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node live-cs-identifier;nody-mc-nodeface", "originDimensionSpacePoint":{"language":"de"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/child-document" to lead to node live-cs-identifier;nodimus-prime", "originDimensionSpacePoint":{"language":"de"}

    When I am in content stream "live-cs-identifier" and dimension space point {"language":"gsw"}
    Then I expect the subgraph projection to consist of exactly 3 nodes
    And I expect node aggregate identifier "lady-eleonode-nodesworth" to lead to node live-cs-identifier;lady-eleonode-nodesworth", "originDimensionSpacePoint":{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node live-cs-identifier;nody-mc-nodeface", "originDimensionSpacePoint":{"language":"gsw"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/child-document" to lead to node live-cs-identifier;nodimus-prime", "originDimensionSpacePoint":{"language":"de"}
