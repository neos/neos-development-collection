@browser
Feature: Content module / Preview mode
  In order to preview changes
  As an editor
  I need a way to see the page without UI elements

  @fixtures @javascript @remote
  Scenario: Toggle preview mode
    Given I imported the site "Neos.Demo"
    And the following users exist:
      | username | password | firstname | lastname | roles  |
      | jdoe     | password | John      | Doe      | Editor |
    And I am authenticated with "jdoe" and "password" for the backend
    Then I should be in the "Content" module
    When I wait for the "Open full screen" button to be visible
    And I press "Full Screen"
    And I wait for the "Close full screen" button to be visible
    Then I should not see the top bar
    And the Previewbutton should be active
