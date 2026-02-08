@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Run benchmark tests on a balanced graph, i.e. 10 children per parent

  Background: The stage is set
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Node': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario Outline: Create a balanced graph of different sizes
    When I decide on sampling begin with result <beginSampling>
    And I create descendants of node "lady-eleonode-rootford" of type "Neos.ContentRepository.Testing:Node" and depth <depth> and breadth <breadth> as sample <sampleName>
    Then I expect linear runtime growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    And I expect logarithmic ID query time growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    And I expect logarithmic child query time growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    And I expect logarithmic parent query time growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    And I expect logarithmic descendant query time growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    And I expect logarithmic ancestor query time growth between samples <firstSample> and <secondSample> with expected factor <expectedFactor>
    Examples:
      | beginSampling | sampleName | depth | breadth | firstSample | secondSample | expectedFactor |
      # 11 nodes
      | true          | twoLevels  | 2     | 10      | null        | null         | 0              |
      # 1,111 nodes
      | false         | fourLevels | 4     | 10      | twoLevels   | fourLevels   | 100            |
