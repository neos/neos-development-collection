Feature: The demo site is rendered when we log into the system.

  Background:
    Given I start with a clean database only once per feature
    Given I execute the flow command "user:create" with the following arguments only once per feature:
      | Name      | Value         |
      | username  | admin         |
      | password  | password      |
      | firstName | A             |
      | lastName  | D             |
      | roles     | Administrator |
    Given I execute the flow command "site:import" with the following arguments only once per feature:
      | Name       | Value     |
      | packageKey | Neos.Demo |
    And I execute the flow command "contentrepositorymigrate:run" only once per feature
  And I am logged in as "admin" "password"

  Scenario: rendering the homepage in backend works
    When I visit "/@user-admin;language=en_US"
    Then the content of the page contains "This website is powered by Neos"
    And the content of the page contains "Imagine this..."
