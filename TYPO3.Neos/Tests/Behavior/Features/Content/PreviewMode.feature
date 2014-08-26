Feature: Content module / Preview mode
  In order to preview changes
  As an editor
  I need a way to see the page without UI elements

  @fixtures @javascript
  Scenario: Toggle preview mode
    Given I imported the site "TYPO3.NeosDemoTypo3Org"
    And the following users exist:
      | username | password | firstname | lastname | roles  |
      | jdoe     | password | John      | Doe      | Editor |
    And I am authenticated with "jdoe" and "password" for the backend
    Then I should be in the "Content" module
    When I press "Full Screen"
    Then I should not see the top bar
    And the Previewbutton should be active
