@fixtures
Feature: Tests for the "Neos.Fusion:DataStructure" Fusion prototype

  Background:
    Given I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DataStructure.Example) < prototype(Neos.Fusion:DataStructure) {
      foo = 'string'
      bar {
        baz = true
        foos {
          bars = 123
          removed >
          null1 = null
          null2 = ${null}
        }
      }
    }
    """

  Scenario: DataStructure (default)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DataStructure.Example {
      @process.toJson = ${Json.stringify(value)}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {"foo":"string","bar":{"baz":true,"foos":{"bars":123,"null2":null}}}
    """

  Scenario: DataStructure (with nulled keys)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DataStructure.Example {
      added = 123.45
      bar.foos >
      @process.toJson = ${Json.stringify(value)}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {"foo":"string","bar":{"baz":true},"added":123.45}
    """

  Scenario: DataStructure (with removed keys)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:Test.DataStructure.Example {
      added = 123.45
      bar.foos >
      @process.toJson = ${Json.stringify(value)}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {"foo":"string","bar":{"baz":true},"added":123.45}
    """

    # @see https://github.com/neos/neos-development-collection/issues/3859
  Scenario: DataStructure (applied null keys)
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      @apply.attributes = ${{
        nullAttribute: null
      }}
      @process.toJson = ${Json.stringify(value)}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {"nullAttribute":null}
    """
