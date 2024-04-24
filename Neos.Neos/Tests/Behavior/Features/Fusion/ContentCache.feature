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

  Scenario:
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'test1'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    test1
    """
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'test2'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    test1
    """


  Scenario:
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'test2'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    test2
    """
    When I execute the following Fusion code:
    """fusion
        test = Neos.Neos:Test.ContentCache {
            foo = 'test1'
        }
    """
    Then I expect the following Fusion rendering result:
    """
    test2
    """