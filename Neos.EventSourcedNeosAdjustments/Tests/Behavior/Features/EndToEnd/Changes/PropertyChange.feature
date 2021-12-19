Feature: Properties can be changed

  Background:
    Given I start with a clean database only once per feature
    Given I execute the flow command "user:create" with the following arguments only once per feature:
      | Name      | Value         |
      | username  | admin         |
      | password  | password      |
      | firstName | A             |
      | lastName  | D             |
      | roles     | Administrator |
    Given I execute the flow command "site:import" with the following arguments only once per feature:
      | Name       | Value     |
      | packageKey | Neos.Demo |
    And I execute the flow command "contentrepositorymigrate:run" only once per feature
    And I am logged in as "admin" "password"
    Given I am in the active content stream of workspace "user-admin" and Dimension Space Point {"language": "en_US"}
    # the "Teaser title" node on the homepage
    And I get the node address for node aggregate "d17caff2-f50c-d30b-b735-9b9216de02e9", remembering it as "TEASERNODE"

  Scenario: Triggering SetProperty on inline editable property
    When I send the following changes:
      | Type                  | Subject Node Address | Payload                                                                  |
      | Neos.Neos.Ui:Property | TEASERNODE           | {"propertyName":"title","value":"<h1>new title XX</h1>","isInline":true} |
    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
    Then the feedback contains "Neos.Neos.Ui:UpdateNodeInfo"

    Then I expect node aggregate identifier "d17caff2-f50c-d30b-b735-9b9216de02e9" to lead to node cs-identifier;d17caff2-f50c-d30b-b735-9b9216de02e9;{"language": "en_US"}
    Then I expect this node to have the following properties:
      | Key   | Value                 |
      | title | <h1>new title XX</h1> |
