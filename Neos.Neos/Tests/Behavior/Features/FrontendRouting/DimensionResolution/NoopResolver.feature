@contentrepository
Feature: NoopResolver does nothing (boilerplate testcase)

  Background:
    Given I have no content dimensions

  Scenario: Match homepage URL
    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory" with options:
    """
    """
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/"

  Scenario: Match homepage URL
    When I am on URL "/foo"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory" with options:
    """
    """
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/foo"
