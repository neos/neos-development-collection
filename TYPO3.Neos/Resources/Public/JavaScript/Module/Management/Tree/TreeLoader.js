Ext.ns('F3.TYPO3.Module.Management.Tree');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.Module.Management.Tree.TreeLoader
 *
 * this is the tree loader component for the default tree view.
 *
 * @namespace F3.TYPO3.Module.Management.Tree
 * @extends Ext.tree.TreeLoader
 */
F3.TYPO3.Module.Management.Tree.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {

	/**
	 * Wrapper for extDirect call to NodeController which
	 * adds the current node path information to the extDirect call
	 *
	 * @param {String} contextNodePath the current Context Node Path to get subnodes from
	 * @param {Function} callback function after request is done
	 * @return {void}
	 */
	directFn: function(contextNodePath, callback) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, 'TYPO3:Page', callback);
	},

	/**
	 * Process the response of directFn and give the appropriate data to handleResponse
	 *
	 * @param {Object} result the result part from the response of the server request
	 * @param {Object} response the response object of the server request
	 * @param {Object} args request arguments passed through
	 * @return {void}
	 */
	processDirectResponse: function(result, response, args) {
		if (response.status) {
			this.handleResponse({
				responseData: Ext.isArray(result.data) ? result.data : null,
				responseText: result,
				argument: args
			});
		} else {
			this.handleFailure({
				argument: args
			});
		}
	}

});

Ext.reg('F3.TYPO3.Module.Management.Tree.TreeLoader', F3.TYPO3.Module.Management.Tree.TreeLoader);