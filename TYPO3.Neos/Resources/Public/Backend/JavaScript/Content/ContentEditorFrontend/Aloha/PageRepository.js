Ext.ns('F3.TYPO3.Content.ContentEditorFrontend.Aloha');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Aloha.Plugin
 *
 * TYPO3 to Aloha connector plugin<br />
 * <br />
 * This plugin tracks content changes by the aloha editor and communicates this
 * information to the TYPO3 backend.
 * This class does NOT run inside the backend, but is included in the Frontend
 * iframe.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Aloha
 * @extends GENTICS.Aloha.Plugin
 * @singleton
 * @todo fix this
 */
F3.TYPO3.Content.ContentEditorFrontend.Aloha.PageRepository = Ext.apply(
	new GENTICS.Aloha.Repository('F3.TYPO3.Content.ContentEditorFrontend.Aloha.PageRepository'),
	{
		settings: {
			weight: 0.35
		},

		/**
		 *
		 */
		query: function(params, callback) {
				// return if no website type is requested
			if (params.objectTypeFilter && jQuery.inArray('website', params.objectTypeFilter) == -1) {
				callback.call(this, []);
			}
			var context = {
					'__context': '/' + window.parent.F3.TYPO3.Configuration.Application.workspaceName + window.parent.F3.TYPO3.Configuration.Application.siteNodePath
				};
			window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodes(context, 'TYPO3:Page', 0, function(result) {
				var items = [];
				Ext.each(result.data, function(item) {
						// TODO Filter on server side!
					if (!item.title.match(params.queryString)) {
						return;
					}
					items.push(new GENTICS.Aloha.Repository.Document ({
						id: item.__nodePath,
						name: item.title,
						repositoryId: this.repositoryId,
						type: 'website',
							// TODO Calculate correct URL
						url: item.__nodePath,
						weight: this.settings.weight
					}));
				}, this);
				callback.call(this, items);
			}, this);
		}
	}
);