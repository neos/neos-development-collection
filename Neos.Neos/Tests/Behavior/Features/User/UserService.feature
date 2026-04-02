@flowEntities
Feature: Neos UserService related features

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the following Neos users exist:
      | Id      | Username | First name | Last name | Roles                                            |
      | janedoe | jane.doe | Jane       | Doe       | Neos.Neos:Administrator                          |
      | johndoe | john.doe | John       | Doe       | Neos.Neos:RestrictedEditor,Neos.Neos:UserManager |
      | editor  | editor   | Edward     | Editor    | Neos.Neos:Editor                                 |

  Scenario: List user accounts not logged in for some time
    When Neos user "jane.doe" last logged in 9 days ago
    And Neos user "john.doe" last logged in 6 days ago
    And Neos user "editor" last logged in 5 days ago
    Then the following users did not log in within 7 days:
      | Id      |
      | janedoe |
    And the following users did not log in within 6 days:
      | Id      |
      | janedoe |
      | johndoe |
    And the following users did not log in within 3 days:
      | Id      |
      | janedoe |
      | johndoe |
      | editor  |
