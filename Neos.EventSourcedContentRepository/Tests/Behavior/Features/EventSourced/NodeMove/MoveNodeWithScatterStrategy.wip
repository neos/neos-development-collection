@fixtures
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | currentContentStreamIdentifier | "cs-identifier"                        |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content': []
    'Neos.ContentRepository.Testing:OtherContent': []
    """

  Scenario: Move node after the last of its siblings

  Scenario: Move node before one of its siblings

  Scenario: Move node to be the last child of a new parent

  Scenario: Move node before a child node of a new parent
