Feature: Nodes can be created

  - CreateInto
  - TODO: CreateAfter
  - TODO: CreateBefore

  for
  - Document Nodes
  - Content Nodes

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
    And I get the node address for the node at path "/neosdemo", remembering it as "HOMEPAGE"
    And I get the node address for the node at path "/neosdemo/main", remembering it as "MAIN"
    And I get the node address for the node at path "/neosdemo/teaser", remembering it as "TEASER_COLLECTION"
    # Teaser text in /neosdemo/teaser
    And I get the node address for node aggregate "d17caff2-f50c-d30b-b735-9b9216de02e9", remembering it as "TEASERTEXT"

# TODO: fix!
  Scenario: CreateInto on Document Nodes
    When I send the following changes:
    # TODO: create a separate testcase with server-generated name (i.e. without "title" property)
      | Type                    | Subject Node Address | Payload                                                                                                                                                                                                                                                     |
      | Neos.Neos.Ui:CreateInto | HOMEPAGE             | {"nodeType":"Neos.NodeTypes:Page", "data": {"title":"newnode"}, "parentContextPath": "HOMEPAGE", "parentDomAddress": {"contextPath": "HOMEPAGE", "fusionPath": "landingPage<Neos.NodeTypes:Page>/body<Neos.Fusion:Template>/footer/__meta/context/node<>"}} |
#    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
#    Then the feedback contains "Neos.Neos.Ui:UpdateNodeInfo"
#    Then the feedback contains "Neos.Neos.Ui:NodeCreated"

    #When the graph projection is fully up to date
    #And I get the node at path "/neosdemo/newnode"
    #And I expect the current Node to have the properties:
    #  | Key            | Value   |
    #  | title          | newnode |
    #  | urlPathSegment | newnode |

# TODO: fix!
#  Scenario: CreateInto on Content Nodes
#    When I send the following changes:
#      | Type                    | Subject Node Address | Payload                                                                                                                                                                                                                                                                                                 |
#      | Neos.Neos.Ui:CreateInto | MAIN                 | {"nodeType":"Neos.NodeTypes:Headline", "parentContextPath": "MAIN", "parentDomAddress": {"contextPath": "MAIN", "fusionPath": "landingPage<Neos.NodeTypes:Page>/body<Neos.Fusion:Template>/content/main<Neos.Neos:PrimaryContent>/default<Neos.Fusion:Matcher>/renderer"}} |
#    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
#    Then the feedback contains "Neos.Neos.Ui:UpdateNodeInfo"
#    Then the feedback contains "Neos.Neos.Ui:RenderContentOutOfBand"
#    Then the feedback contains "Neos.Neos.Ui:NodeCreated"

# TODO: fix!
#  Scenario: CreateAfter on Content Nodes
#    When I send the following changes:
#      | Type                     | Subject Node Address | Payload                                                                                                                                                                                                                                         |
#      | Neos.Neos.Ui:CreateAfter | TEASERTEXT           | {"nodeType":"Neos.NodeTypes:Headline", "siblingDomAddress": {"contextPath": "TEASERTEXT"}, "parentDomAddress": {"contextPath": "TEASER_COLLECTION", "fusionPath": "landingPage<Neos.NodeTypes:Page>/body<Neos.Fusion:Template>/content/teaser"}} |
#    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
#    Then the feedback contains "Neos.Neos.Ui:UpdateNodeInfo"
#    Then the feedback contains "Neos.Neos.Ui:RenderContentOutOfBand"
#    Then the feedback contains "Neos.Neos.Ui:NodeCreated"
