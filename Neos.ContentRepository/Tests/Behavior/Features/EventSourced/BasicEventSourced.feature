@fixtures
Feature: Basic evetn source
  Create

  Scenario:
    Given The Event "Neos.ContentRepository:RootNodeWasCreated" was published to stream "foo" with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |


  Scenario: Root Node is created
    When the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event number 1 is:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |

