Feature: The demo site renders

  Background:
    Given I start with a clean database only once per feature
    And I execute the flow command "site:import" with the following arguments only once per feature:
      | Name       | Value     |
      | packageKey | Neos.Demo |
    And I execute the flow command "contentrepositorymigrate:run" only once per feature

  Scenario: rendering the homepage basically works
    When I visit "/"
    Then the content of the page contains "This website is powered by Neos"
    And the content of the page contains "Imagine this..."
    And the URL path is "/"

    # visiting the "features" page
    When I visit "/en/features.html"
    Then the content of the page contains "Built for Extensibility"

  Scenario: not in menu-pages won't render
    When I visit "/"
    Then the content of the page does not contain "Not Found"

