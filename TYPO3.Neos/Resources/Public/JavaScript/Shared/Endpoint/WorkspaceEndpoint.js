define([
	'emberjs',
	'./../HttpClient'
], function(
	Ember,
	HttpClient
) {
	return Ember.Object.create({
		publishNode: function(node, targetWorkspaceName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-publishNode'),
				{data: {node: node, targetWorkspaceName: targetWorkspaceName}}
			);
		},

		publishNodes: function(nodes, targetWorkspaceName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-publishNodes'),
				{data: {nodes: nodes, targetWorkspaceName: targetWorkspaceName}}
			);
		},

		publishAll: function(sourceWorkspaceName, targetWorkspaceName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-publishAll'),
				{data: {sourceWorkspaceName: sourceWorkspaceName, targetWorkspaceName: targetWorkspaceName}}
			);
		},

		discardNode: function(node) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-discardNode'),
				{data: {node: node}}
			);
		},

		discardNodes: function(nodes) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-discardNodes'),
				{data: {nodes: nodes}}
			);
		},

		getWorkspaceWideUnpublishedNodes: function(workspaceName) {
			return HttpClient.getResource(
				HttpClient._getEndpointUrl('neos-service-workspace-getWorkspaceWideUnpublishedNodes'),
				{data: {workspace: workspaceName}}
			);
		},

		discardAll: function(workspaceName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-discardAll'),
				{data: {workspace: workspaceName}}
			);
		}
	});
});