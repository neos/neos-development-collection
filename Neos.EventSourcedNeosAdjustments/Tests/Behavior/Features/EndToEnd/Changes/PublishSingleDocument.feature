Feature: Publishing a single document works

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
    # the "left column title "Built for Extensibility"" node on the features page
    And I get the node address for node aggregate "69e833bd-b3a9-e0e2-6d93-590b6aa93ead", remembering it as "FEATURESLEFTTEXTNODE"

    When I send the following changes:
      | Type                  | Subject Node Address | Payload                                                                  |
      | Neos.Neos.Ui:Property | TEASERNODE           | {"propertyName":"title","value":"<h1>new title XX</h1>","isInline":true} |

    And I send the following changes:
      | Type                  | Subject Node Address | Payload                                                              |
      | Neos.Neos.Ui:Property | FEATURESLEFTTEXTNODE | {"propertyName":"title","value":"<h1>new text</h1>","isInline":true} |


  Scenario: Publish only single node
    When I publish the following nodes to "live" workspace:
      | Subject Node Address |
      | TEASERNODE           |
    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
    Then the feedback contains "Neos.Neos.Ui:Success"

    When I am in the active content stream of workspace "live" and Dimension Space Point {"language": "en_US"}
    Then I expect node aggregate identifier "d17caff2-f50c-d30b-b735-9b9216de02e9" to lead to node cs-identifier;d17caff2-f50c-d30b-b735-9b9216de02e9;{"language": "en_US"}
    Then I expect this node to have the following properties:
      | Key   | Value                 |
      | title | <h1>new title XX</h1> |

    Then I expect node aggregate identifier "69e833bd-b3a9-e0e2-6d93-590b6aa93ead" to lead to node cs-identifier;69e833bd-b3a9-e0e2-6d93-590b6aa93ead;{"language": "en_US"}
    Then I expect this node to have the following properties:
      | Key   | Value                            |
      | title | <h4>Built for Extensibility</h4> |

    When I am in the active content stream of workspace "user-admin" and Dimension Space Point {"language": "en_US"}
    Then I expect node aggregate identifier "d17caff2-f50c-d30b-b735-9b9216de02e9" to lead to node user-cs-identifier;d17caff2-f50c-d30b-b735-9b9216de02e9;{"language": "en_US"}
    Then I expect this node to have the following properties:
      | Key   | Value                 |
      | title | <h1>new title XX</h1> |

    Then I expect node aggregate identifier "69e833bd-b3a9-e0e2-6d93-590b6aa93ead" to lead to node user-cs-identifier;69e833bd-b3a9-e0e2-6d93-590b6aa93ead;{"language": "en_US"}
    Then I expect this node to have the following properties:
      | Key   | Value             |
      | title | <h1>new text</h1> |
