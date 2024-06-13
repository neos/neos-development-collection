@contentrepository
Feature: NoopResolver does nothing (boilerplate testcase)

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Match homepage URL
    When I am on URL "/"
    And I invoke the Dimension Resolver from site configuration:
    """yaml
    contentRepository: default
    contentDimensions:
      resolver:
        factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/"

  Scenario: Match homepage URL
    When I am on URL "/foo"
    And I invoke the Dimension Resolver from site configuration:
    """yaml
    contentRepository: default
    contentDimensions:
      resolver:
        factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/foo"
