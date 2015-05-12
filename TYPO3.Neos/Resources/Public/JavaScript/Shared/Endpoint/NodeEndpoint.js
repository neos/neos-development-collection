define([
	'emberjs',
	'./../HttpClient'
], function(
	Ember,
	HttpClient
) {
	return Ember.Object.create({
		_saveRunning: false,
		_lastSuccessfulTransfer: null,

		getChildNodesForTree: function(node, nodeTypeFilter, depth, untilNode) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-getChildNodesForTree'),
				{data: {node: node, nodeTypeFilter: nodeTypeFilter, depth: depth, untilNode: untilNode}}
			);
		},

		filterChildNodesForTree: function(node, term, nodeType) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-filterChildNodesForTree'),
				{data: {node: node, term: term, nodeType: nodeType}}
			);
		},

		create: function(referenceNode, nodeData, position) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-create'),
				{data: {referenceNode: referenceNode, nodeData: nodeData, position: position}}
			);
		},

		createAndRender: function(referenceNode, typoScriptPath, nodeData, position) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-createAndRender'),
				{data: {referenceNode: referenceNode, typoScriptPath: typoScriptPath, nodeData: nodeData, position: position}}
			);
		},

		createNodeForTheTree: function(referenceNode, nodeData, position) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-createNodeForTheTree'),
				{data: {referenceNode: referenceNode, nodeData: nodeData, position: position}}
			);
		},

		move: function(node, targetNode, position) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-move'),
				{data: {node: node, targetNode: targetNode, position: position}}
			);
		},

		copy: function(node, targetNode, position, nodeName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-copy'),
				{data: {node: node, targetNode: targetNode, position: position, nodeName: nodeName}}
			);
		},

		update: function(node) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-update'),
				{data: {node: node}}
			);
		},

		updateAndRender: function(node, typoScriptPath) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-updateAndRender'),
				{data: {node: node, typoScriptPath: typoScriptPath}}
			);
		},

		'delete': function(node) {
			return HttpClient.deleteResource(
				HttpClient._getEndpointUrl('neos-service-node-delete'),
				{data: {node: node}}
			);
		},

		searchPage: function(query) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-searchPage'),
				{data: {query: query}}
			);
		},

		getPageByNodePath: function(nodePath) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-getPageByNodePath'),
				{data: {nodePath: nodePath}}
			);
		}
	});
});