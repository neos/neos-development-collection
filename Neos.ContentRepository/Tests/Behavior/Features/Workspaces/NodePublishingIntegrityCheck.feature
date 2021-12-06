@fixtures
Feature: Node publishing integrity check

  See https://github.com/neos/neos-development-collection/issues/3383 for details
  Let's say we have the following Node structure in the beginning:

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
  a text change on subpage.

  |-- site
  . |-- cr
  .   |-- subpage   live + SHADOW NODE in user-demo <--- (2) ... and this is published as well; removing the subpage in live
  .     |-- nested  live + SHADOW NODE in user-demo
  . |-- other
  .   |-- subpage   user-demo <-- (1) only this is published.
  .     |-- nested  user-demo
  This leads to the following result:

  |-- site
  . |-- cr
  .   |-- [NODE DOES NOT EXIST ANYMORE]
  .     |-- nested  live + SHADOW NODE in user-demo   <-- !!BUG!!
  . |-- other
  .   |-- subpage
  .     |-- nested  user-demo
  The first "nested" node (marked with !!BUG!!) is NOT visible anymore in live, because the parent does not exist
  anymore. It's hard to detect this as user-demo, because user-demo sees the moved nested node.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets |
      | language   | de      | de=de   |
    Given I have the following nodes:
      | Identifier                           | Path                  | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                | unstructured                        |                       | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/cr             | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/subpage        | Neos.ContentRepository.Testing:Page | {"title": "Subpage"}  | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/subpage/nested | Neos.ContentRepository.Testing:Page | {"title": "Nested"}   | de       |
      | 498414ca-d211-4eee-a5ec-f54c056bfc3e | /sites/other          | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | de       |
    And I am authenticated with role "Neos.Neos:Editor"

  Scenario: moves /site/cr/subpage underneath /site/other/ and publishes /site/other/subpage => SHOULD WORD
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    # move nodes around
    And I move the node into the node with path "/sites/other"

    When I get a node by path "/sites/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    And I publish the node

    # Assertions: node was published successfully
    Then I get a node by path "/sites/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root

  Scenario: moves /site/cr/subpage underneath /site/other/ and only publishes /site/other/subpage/other => SHOULD FAIL
    When I get a node by path "/sites/cr/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    # move nodes around
    And I move the node into the node with path "/sites/other"

    # get child node
    When I get a node by path "/site/other/subpage" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I publish the node
    And I publish the node and exceptions are caught
    And the last caught exception should be of type "TodoException" with message:
    """
      Exception: TODO
    """

    # Assertions: node was NOT published
    Then I get a node by path "/site/other/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

    # and the node still exists at the old location
    # Assertions: node was NOT published
    Then I get a node by path "/site/cr/subpage" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node should be connected to the root
