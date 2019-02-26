#Feature: Publishing a single document works
#
#  Background:
#    Given I start with a clean database only once per feature
#    Given I execute the flow command "user:create" with the following arguments only once per feature:
#      | Name      | Value         |
#      | username  | admin         |
#      | password  | password      |
#      | firstName | A             |
#      | lastName  | D             |
#      | roles     | Administrator |
#    Given I execute the flow command "site:import" with the following arguments only once per feature:
#      | Name       | Value     |
#      | packageKey | Neos.Demo |
#    And I execute the flow command "contentrepositorymigrate:run" only once per feature
#    And I am logged in as "admin" "password"
#    Given I am in the active content stream of workspace "user-admin" and Dimension Space Point {"language": "en_US"}
#    # the "Teaser title" node on the homepage
#    And I get the node address for node aggregate "d17caff2-f50c-d30b-b735-9b9216de02e9", remembering it as "TEASERNODE"
#    # the "left column text" node on the features page
#    And I get the node address for node aggregate "ac55924c-9a74-140d-7733-8fbc7048bdd9", remembering it as "FEATURESLEFTTEXTNODE"
#
#    When I send the following changes:
#      | Type                  | Subject Node Address | Payload                                                                  |
#      | Neos.Neos.Ui:Property | TEASERNODE           | {"propertyName":"title","value":"<h1>new title XX</h1>","isInline":true} |
#
#    And I send the following changes:
#      | Type                  | Subject Node Address | Payload                                                             |
#      | Neos.Neos.Ui:Property | FEATURESLEFTTEXTNODE | {"propertyName":"text","value":"<h1>new text</h1>","isInline":true} |
#
#
#  Scenario: Publish only single node
#    When I publish the following nodes to "live" workspace:
#      | Subject Node Address |
#      | TEASERNODE           |
#    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
#    Then the feedback contains "Neos.Neos.Ui:Success"
#
#    When I am in the active content stream of workspace "live" and Dimension Space Point {"language": "en_US"}
#    Then I expect a node identified by aggregate identifier "d17caff2-f50c-d30b-b735-9b9216de02e9" to exist in the subgraph
#    And I expect the current Node to have the properties:
#      | Key   | Value                 |
#      | title | <h1>new title XX</h1> |
#
#    Then I expect a node identified by aggregate identifier "ac55924c-9a74-140d-7733-8fbc7048bdd9" to exist in the subgraph
#    And I expect the current Node to have the properties:
#      | Key  | Value             |
#      | text | <h1>new text</h1> |
