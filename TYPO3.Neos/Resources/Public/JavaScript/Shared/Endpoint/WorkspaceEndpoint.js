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

		publishAll: function(workspaceName) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-publishAll'),
				{data: {workspaceName: workspaceName}}
			);
		},

		discardNode: function(node) {
			return HttpClient.updateResource(
				HttpClient._getEndpointUrl('neos-service-workspace-discardNode'),
				{data: {node: node}}
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