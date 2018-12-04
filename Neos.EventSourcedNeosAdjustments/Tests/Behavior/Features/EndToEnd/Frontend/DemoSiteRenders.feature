@fixtures
Feature: The demo site renders

  Background:
    Given I execute the flow command "site:import" with the following arguments:
      | Name       | Value     |
      | packageKey | Neos.Demo |
    And I execute the flow command "contentrepositorymigrate:run"

  Scenario: rendering the homepage basically works
    When I visit "/"
    Then the content of the page contains "This website is powered by Neos"
    And the content of the page contains "Imagine this..."
#    And the URL is "/"

