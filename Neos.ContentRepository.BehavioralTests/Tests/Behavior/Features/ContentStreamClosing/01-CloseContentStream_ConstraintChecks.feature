@contentrepository @adapters=DoctrineDBAL
Feature: Constraint check test cases for closing content streams

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Neos.ContentRepository:Root: {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date

  Scenario: Try to close a non-existing content stream:
    And the command CloseContentStream is executed with payload and exceptions are caught:
      | Key             | Value            |
      | contentStreamId | "i-do-not-exist" |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to close a content stream that is already closed:
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    And the command CloseContentStream is executed with payload and exceptions are caught:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"
