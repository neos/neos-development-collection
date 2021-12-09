@fixtures
Feature: Node publishing integrity check

  Let's say we have the following node structure in the beginning:

  |-- site
  . |-- cr
  . | |-- subpage
  . |   |-- nested
  . |-- other

  Now, user-demo moves /site/cr/subpage underneath /site/other/ in the user workspace. This means in the
  user workspace the following status exists:
  |-- site
  . |-- cr
  .   |-- subpage   SHADOW NODE in user-demo
  .     |-- nested  SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo
  .     |-- nested  user-demo

  Now, let's assume user-demo forgets about this (thus not publishing), and a few weeks later needs to do
  a text change on subpage:
  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo
  .     |-- nested  live + SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo <-- THIS node gets edited by user-demo
  .     |-- nested  user-demo

  Now user-demo publishes only  /sites/other/subpage which leads to the following structure:
  |-- site
  . |-- cr
  .   |-- [NODE DOES NOT EXIST ANYMORE]
  .     |-- nested  live + SHADOW NODE in user-demo   <-- !!BUG!!
  . |-- other
  .   |-- subpage
  .     |-- nested  user-demo

  The first "nested" node (marked with !!BUG!!) is NOT visible anymore in live, because the parent does not exist
  anymore. It's hard to detect this as user-demo, because user-demo sees the moved nested node.

  We want to prevent those structures get created by forbidding those partial publishes in the first place!

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets |
      | language   | de      | de=de   |
    Given I have the following nodes:
      | Identifier                           | Path                     | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                   | unstructured                        |                       | de       |
      | 594cd631-cf19-4072-9ee8-f8d840e85f5f | /sites/cr                | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       |
      | 7378845c-79cc-464c-90cf-03ec9ed551e8 | /sites/cr/subpage        | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}  | de       |
      | 97d7a295-a3ed-44ec-bed3-22d501920578 | /sites/cr/subpage/nested | Neos.ContentRepository.Testing:Page | {"title": "Nested"}   | de       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | de       |
    And I am authenticated with role "Neos.Neos:Editor"

  # We only move a single document node. No parents, no children. This should always work
  Scenario: moves /site/cr/subpage/nested underneath /site/other/ and publishes /site/other/nested => SHOULD WORK
    Given I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move node and publish
    When I move the node into the node with path "/sites/other"
    And I get a node by path "/sites/other/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I publish the node

    # Assertions: node was published successfully
    And I get a node by path "/sites/other/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

    # Assertions: node does not exists on source location anymore
    When I get a node by path "/sites/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  # We move a parent and its child and only publish the child => ERROR
  Scenario: moves /site/cr/subpage underneath /site/other/ and only publishes /site/other/subpage/nested => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move nodes and publish
    When I move the node into the node with path "/sites/other"
    When I get a node by path "/sites/other/subpage/nested" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I publish the node and exceptions are caught
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
      Exception: TODO
    """

    # Assertions: node was NOT published
    When I get a node by path "/site/other/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertion: the node still exists at the old location
    When I get a node by path "/site/cr/subpage/nested" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  # We move a parent and its child and only publish the parent-node => ERROR
  Scenario: moves /site/cr/subpage underneath /site/other/ and only publishes /site/other/subpage => SHOULD FAIL
    Given I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |

    # move nodes and publish
    When I move the node into the node with path "/sites/other"
    When I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I publish the node and exceptions are caught
    Then the last caught exception should be of type "NodePublishingIntegrityCheckViolationException" with message:
    """
      Exception: TODO
    """

    # Assertions: node was NOT published
    When I get a node by path "/site/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # Assertion: the node still exists at the old location
    When I get a node by path "/site/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
