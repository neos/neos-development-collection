@browser
Feature: Site management / List sites
  In order to manage sites
  As an administrator
  I need a way to list and manage sites

  Background:
    Given I imported the site "Neos.Demo"
    And the following users exist:
      | username | password | firstname | lastname | roles         |
      | jdoe     | password | John      | Doe      | Administrator |
    And I am authenticated with "jdoe" and "password" for the backend

  @fixtures @remote
  Scenario: List sites
    When I go to the "Administration / Site Management" module
    Then I should see the following sites in a table:
      | name           |
      | Neos Demo Site |

  # Scenario: Add site from existing package

  @fixtures @remote
  Scenario: Add site by creating a new package
    When I go to the "Administration / Site Management" module
    And I follow "Add new site"
    And I fill in "Package Key" with "Test.DemoSite"
    And I fill in "Site Name" with "Test Demo Site"
    And I press "Create"
    Then I should see the following sites in a table:
      | name                 |
      | Test Demo Site       |
      | Neos Demo Site       |

  @fixtures @javascript @remote
  Scenario: Update site name
    When I go to the "Administration / Site Management" module
    And I follow "Click to edit" for site "Neos Demo Site"
    And I fill in "name" with "Updated Neos Demo Site"
    And I press "Save"
    Then I should see the following sites in a table:
      | name                   |
      | Updated Neos Demo Site |
