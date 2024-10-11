@contentrepository @LegacyMigration
Feature: Migrating nodes with moved nodes in multiple content dimensions
  Background:
    Given using the following content dimensions:
      | Identifier | Default | Values | Generalizations |
      | language   | en      | en, de ||
    And using the following node types:
    """yaml

    'unstructured': {}
    'Neos.Neos:Site': {}
    'Neos.Neos:Document':
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'

    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Content': {}


    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Replaying content projections
    When I have the following node data rows:
      | Path                                                          | Identifier                | Node Type                   | Dimension Values     |
      | /sites                                                        | sites                     | unstructured                |                      |
      | /sites/neosdemo	                                              | site	                    | Neos.Neos:Site	            | {"language": ["de"]} |
      | /sites/neosdemo	                                              | site	                    | Neos.Neos:Site	            | {"language": ["en"]} |
      | /sites/neosdemo/node-a	                                      | node-a	                  | Neos.Neos:Document	        | {"language": ["en"]} |
      | /sites/neosdemo/node-a	                                      | node-a	                  | Neos.Neos:Document	        | {"language": ["de"]} |
      | /sites/neosdemo/node-b	                                      | node-b	                  | Neos.Neos:Document	        | {"language": ["de"]} |

    And I run the event migration
    And I import all events for content stream "testing-projections"
    And I replay all content projections
    Then I expect the following events to be exported
      | Type                                | Payload         |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"}], "nodeAggregateClassification": "root"} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site", "parentNodeAggregateId": "sites",  "originDimensionSpacePoint":{"language":"de"}}  |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "site", "sourceOrigin":{"language":"de"},"peerOrigin":{"language":"en"}} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-a", "parentNodeAggregateId": "site", "originDimensionSpacePoint":{"language":"en"}} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "node-a", "sourceOrigin":{"language":"en"},"peerOrigin":{"language":"de"}} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-b", "parentNodeAggregateId": "site",  "originDimensionSpacePoint":{"language":"de"}} |
    And I expect exactly 6 nodes to be imported

  Scenario: Moved Nodes do not throw errors
    When I have the following node data rows:
      | Path                                                          | Identifier                | Node Type                   | Dimension Values     |
      | /sites                                                        | sites                     | unstructured                |                      |
      | /sites/neosdemo	                                              | site	                    | Neos.Neos:Site	            | {"language": ["de"]} |
      | /sites/neosdemo	                                              | site	                    | Neos.Neos:Site	            | {"language": ["en"]} |
      | /sites/neosdemo/node-a	                                      | node-a	                  | Neos.Neos:Document	        | {"language": ["en"]} |
      | /sites/neosdemo/node-a	                                      | node-a	                  | Neos.Neos:Document	        | {"language": ["de"]} |
      | /sites/neosdemo/node-b	                                      | node-b	                  | Neos.Neos:Document	        | {"language": ["de"]} |
      | /sites/neosdemo/node-b/main	                                  | node-b-main	              | Neos.Neos:ContentCollection	| {"language": ["de"]} |
      | /sites/neosdemo/node-b/main/node-b-column	                    | node-b-column	            | Neos.Neos:Content	          | {"language": ["de"]} |
      | /sites/neosdemo/node-b/main/node-b-column/column0	            | node-b-column-column0	    | Neos.Neos:ContentCollection	| {"language": ["de"]} |
      | /sites/neosdemo/node-b/main/node-b-column/column0/node-b-text	| moved-node	              | Neos.Neos:Content	          | {"language": ["de"]} |
      | /sites/neosdemo/node-a/main	                                  | node-a-main	              | Neos.Neos:ContentCollection | {"language": ["en"]} |
      | /sites/neosdemo/node-a/main	                                  | node-a-main	              | Neos.Neos:ContentCollection | {"language": ["de"]} |
      | /sites/neosdemo/node-a/main/node-a-column	                    | node-a-column	            | Neos.Neos:Content	          | {"language": ["de"]} |
      | /sites/neosdemo/node-a/main/node-a-column	                    | node-a-column	            | Neos.Neos:Content	          | {"language": ["en"]} |
      | /sites/neosdemo/node-a/main/node-a-column/column0	            | node-a-column-column0	    | Neos.Neos:ContentCollection	| {"language": ["de"]} |
      | /sites/neosdemo/node-a/main/node-a-column/column0	            | node-a-column-column0	    | Neos.Neos:ContentCollection	| {"language": ["en"]} |
      | /sites/neosdemo/node-a/main/node-a-column/column0/node-a-text | moved-node	              | Neos.Neos:Content	          | {"language": ["en"]} |

    And I run the event migration
    And I import all events for content stream "testing"
    And I replay all content projections
    Then I expect the following events to be exported
      | Type                                | Payload |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"}], "nodeAggregateClassification": "root"} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site", "parentNodeAggregateId": "sites",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "site", "sourceOrigin":{"language":"de"},"peerOrigin":{"language":"en"},"peerCoverage":[{"language":"en"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-a", "parentNodeAggregateId": "site",  "coveredDimensionSpacePoints": [{"language": "en"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "node-a", "sourceOrigin":{"language":"en"},"peerOrigin":{"language":"de"},"peerCoverage":[{"language":"de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-b", "parentNodeAggregateId": "site",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-b-main", "parentNodeAggregateId": "node-b",  "coveredDimensionSpacePoints": [{"language": "de"}]}  |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-b-column", "parentNodeAggregateId": "node-b-main",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-b-column-column0", "parentNodeAggregateId": "node-b-column",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "moved-node", "parentNodeAggregateId": "node-b-column-column0",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-a-main", "parentNodeAggregateId": "node-a",  "coveredDimensionSpacePoints": [{"language": "en"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "node-a-main", "sourceOrigin":{"language":"en"},"peerOrigin":{"language":"de"},"peerCoverage":[{"language":"de"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-a-column", "parentNodeAggregateId": "node-a-main",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "node-a-column", "sourceOrigin":{"language":"de"},"peerOrigin":{"language":"en"},"peerCoverage":[{"language":"en"}]} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "node-a-column-column0", "parentNodeAggregateId": "node-a-column",  "coveredDimensionSpacePoints": [{"language": "de"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "node-a-column-column0", "sourceOrigin":{"language":"de"},"peerOrigin":{"language":"en"},"peerCoverage":[{"language":"en"}]} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "moved-node", "sourceOrigin":{"language":"de"},"peerOrigin":{"language":"en"},"peerCoverage":[{"language":"en"}]} |
      | NodeAggregateWasMoved               | {"nodeAggregateId": "moved-node", "nodeMoveMappings":[{"movedNodeOrigin":{"language":"en"},"newLocations":[{"coveredDimensionSpacePoint":{"language":"en"},"newSucceedingSibling":{"nodeAggregateId":"node-a-column-column0","originDimensionSpacePoint":{"language":"de"},"parentNodeAggregateId":"node-b-column-column0","parentOriginDimensionSpacePoint":{"language":"de"}}}]}]} |
    And I expect exactly 17 nodes to be imported


