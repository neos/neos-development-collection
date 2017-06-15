define([
	'emberjs',
	'Library/jquery-with-dependencies',
	'./../HttpClient'
], function(
	Ember,
	$,
	HttpClient
) {
	return Ember.Object.create({
		_saveRunning: false,
		_lastSuccessfulTransfer: null,

		getChildNodesForTree: function(node, nodeTypeFilter, depth, untilNode, optionsOverride) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-getChildNodesForTree'),
				$.extend({data: {node: node, nodeTypeFilter: nodeTypeFilter, depth: depth, untilNode: untilNode}}, optionsOverride || {})
			);
		},

		filterChildNodesForTree: function(node, term, nodeType, optionsOverride) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-filterChildNodesForTree'),
				$.extend({data: {node: node, term: term, nodeType: nodeType}}, optionsOverride || {})
			);
		},

		create: function(referenceNode, nodeData, position, optionsOverride) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-create'),
				$.extend({data: {referenceNode: referenceNode, nodeData: nodeData, position: position}}, optionsOverride || {})
			);
		},

		createAndRender: function(referenceNode, fusionPath, nodeData, position, optionsOverride) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-createAndRender'),
				$.extend({data: {referenceNode: referenceNode, fusionPath: fusionPath, nodeData: nodeData, position: position}}, optionsOverride || {})
			);
		},

		createNodeForTheTree: function(referenceNode, nodeData, position, nodeTypeFilter, optionsOverride) {
			return HttpClient.createResource(
				HttpClient._getEndpointUrl('neos-service-node-createNodeForTheTree'),
				$.extend({data: {referenceNode: referenceNode, nodeData: nodeData, nodeTypeFilter: nodeTypeFilter, position: position}}, optionsOverride || {})
			);
		},

		move: function(node, targetNode, position, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-move'),
				$.extend({data: {node: node, targetNode: targetNode, position: position}}, optionsOverride || {})
			);
		},

		moveAndRender: function(node, targetNode, position, fusionPath, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-moveAndRender'),
				$.extend({data: {node: node, targetNode: targetNode, position: position, fusionPath: fusionPath}}, optionsOverride || {})
			);
		},

		copy: function(node, targetNode, position, nodeName, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-copy'),
				$.extend({data: {node: node, targetNode: targetNode, position: position, nodeName: nodeName}}, optionsOverride || {})
			);
		},

		copyAndRender: function(node, targetNode, position, fusionPath, nodeName, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-copyAndRender'),
				$.extend({data: {node: node, targetNode: targetNode, position: position, fusionPath: fusionPath, nodeName: nodeName}}, optionsOverride || {})
			);
		},

		update: function(node, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-update'),
				$.extend({data: {node: node}}, optionsOverride || {})
			);
		},

		updateAndRender: function(node, fusionPath, optionsOverride) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-node-updateAndRender'),
				$.extend({data: {node: node, fusionPath: fusionPath}}, optionsOverride || {})
			);
		},

		'delete': function(node, optionsOverride) {
			return HttpClient.deleteResource(
				HttpClient._getEndpointUrl('neos-service-node-delete'),
				$.extend({data: {node: node}}, optionsOverride || {})
			);
		},

		searchPage: function(query, optionsOverride) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-node-searchPage'),
				$.extend({data: {query: query}}, optionsOverride || {})
			);
		}
	});
});
