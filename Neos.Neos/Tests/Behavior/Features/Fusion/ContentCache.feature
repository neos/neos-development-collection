@flowEntities @contentrepository
Feature: Tests for Fusion ContentCache
  Background:
    Given I have Fusion content cache enabled
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.ContentCache) < prototype(Neos.Fusion:Component) {
        foo = ''
        renderer = ${props.foo}
        @cache {
            mode = 'cached'
            entryIdentifier {
                test = 'test'
            }
        }
    }

"""

  Scenario: Render a cached prototype and check if rerendering doesn't happen on second try
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'some-cached-string'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    some-cached-string
    """
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'some-other-string'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    some-cached-string
    """


  Scenario: Check if cached got flushed before running a new scenario and no leftover of last test is there
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'some-new-string'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    some-new-string
    """
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'totally-different-string'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    some-new-string
    """