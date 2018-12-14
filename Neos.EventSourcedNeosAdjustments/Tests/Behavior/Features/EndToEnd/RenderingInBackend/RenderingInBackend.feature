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

  Scenario: rendering the features page in user workspace works
    When I visit "/features@user-admin;language=en_US.html"
    Then the content of the page contains "Built for Extensibility"

  Scenario: the redirect to the "features" page is built correctly
    Given I am in the active content stream of workspace "user-admin" and Dimension Space Point {"language": "en_US"}
    # features page
    And I get the node address for node aggregate "a3474e1d-dd60-4a84-82b1-18d2f21891a3"
    When I visit "/neos/redirect?node=CURRENT_NODE_ADDRESS"
    Then the URL path is "/features@user-admin;language=en_US.html"
    And the content of the page contains "Built for Extensibility"
