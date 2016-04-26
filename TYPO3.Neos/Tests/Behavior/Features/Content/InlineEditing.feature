@browser
Feature: Content module / Inline editing
  In order to edit content easily
  As an editor
  I need a way to edit content inline

  @fixtures @javascript @remote
  Scenario: Edit text of a content element with automatic save
    Given I imported the site "Neos.Demo"
    And the following users exist:
      | username | password | firstname | lastname | roles  |
      | jdoe     | password | John      | Doe      | Editor |
    And I am authenticated with "jdoe" and "password" for the backend
    Then I should be in the "Content" module
    When I select the first headline content element
    And I set the content to "NewContent"
    And I wait for the changes to be saved
    And I reload the page
    Then I should see "NewContent"
