@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding root connection

  As a user of the CR I want to be able to check whether there are nodes that are not connected to a root node.
  This is the first part of (a)cyclicality checks

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date

  Scenario: Create a cycle
    When the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | nodeAggregateClassification | "root"                                    |
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "sir-david-nodenborough"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeName                    | "document"                                |
      | nodeAggregateClassification | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "nody-mc-nodeface"                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeName                    | "child-document"                          |
      | nodeAggregateClassification | "regular"                                 |
    And the graph projection is fully up to date
    And the event NodeAggregateWasMoved was published with payload:
      | Key                               | Value                                                                                                                                                                                                                                                                                                                                                            |
      | contentStreamId                   | "cs-identifier"                                                                                                                                                                                                                                                                                                                                                  |
      | nodeAggregateId                   | "sir-david-nodenborough"                                                                                                                                                                                                                                                                                                                                         |
      | nodeMoveMappings                  | [{"movedNodeOrigin":{"language":"de"},"newLocations":[{"coveredDimensionSpacePoint": {"language":"de"},"newParent":{"nodeAggregateId":"nody-mc-nodeface","originDimensionSpacePoint":{"language":"de"}}}, {"coveredDimensionSpacePoint": {"language":"gsw"},"newParent":{"nodeAggregateId":"nody-mc-nodeface","originDimensionSpacePoint":{"language":"de"}}}]}] |
    And the graph projection is fully up to date
    And I run integrity violation detection
    # one error per subgraph
    Then I expect the integrity violation detection result to contain exactly 2 errors
    And I expect integrity violation detection result error number 1 to have code 1597754245
    And I expect integrity violation detection result error number 2 to have code 1597754245
