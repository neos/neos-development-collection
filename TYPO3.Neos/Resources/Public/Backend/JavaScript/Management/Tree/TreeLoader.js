Ext.ns("F3.TYPO3.Management.Tree");

/**
 * @class F3.TYPO3.Management.Tree.TreeLoader
 * @namespace F3.TYPO3.Management.Tree
 * @extends Ext.tree.TreeLoader
 * @author Christian Müller <christian@kitsunet.de>
 *
 * The Management Tree loader
 */
F3.TYPO3.Management.Tree.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {

	directFn: function(currentNodePath, callback) {
		var context = {
			'__context': {
				workspaceName: 'live',
				nodePath: currentNodePath
			}
		};
		F3.TYPO3_Controller_NodeController.getChildNodes(context, 'TYPO3:Page', callback);
	},

	processDirectResponse: function(result, response, args){
		if(response.status){
			this.handleResponse({
				responseData: Ext.isArray(result.data) ? result.data : null,
				responseText: result,
				argument: args
			});
		}else{
			this.handleFailure({
				argument: args
			});
		}
	}

});

Ext.reg('F3.TYPO3.Management.Tree.TreeLoader', F3.TYPO3.Management.Tree.TreeLoader);