/**
 * Content structure tree
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./AbstractNodeTree',
	'Content/Application',
	'vie/entity',
	'vie/instance',
	'Shared/Configuration',
	'Shared/Notification',
	'../Model/NodeSelection',
	'./NavigatePanelController',
	'./InsertNodePanel',
	'text!./ContextStructureTree.html'
], function(
	Ember,
	$,
	AbstractNodeTree,
	ContentModule,
	EntityWrapper,
	InstanceWrapper,
	Configuration,
	Notification,
	NodeSelection,
	NavigatePanelController,
	InsertNodePanel,
	template
) {
	var pageMetaInformation = $('#neos-page-metainformation');

	return AbstractNodeTree.extend({
		elementId: ['neos-context-structure'],
		template: Ember.Handlebars.compile(template),
		controller: NavigatePanelController,
		nodeSelection: NodeSelection,
		baseNodeType: '!TYPO3.Neos:Document',
		treeSelector: '#neos-context-structure-tree',
		loadingDepth: 0,
		unmodifiableLevels: 2,

		init: function() {
			this.set('insertNodePanel', InsertNodePanel.extend({baseNodeType: 'TYPO3.Neos:Content'}).create());
			var that = this;
			ContentModule.on('pageLoaded', function() {
				var pageMetaInformation = $('#neos-page-metainformation'),
					pageNodePath = pageMetaInformation.attr('about'),
					page = InstanceWrapper.entities.get(InstanceWrapper.service('rdfa').getElementSubject(pageMetaInformation)),
					namespace = Configuration.get('TYPO3_NAMESPACE'),
					pageTitle = typeof page !== 'undefined' && typeof page.get(namespace + 'title') !== 'undefined' ? page.get(namespace + 'title') : pageNodePath,
					siteNode = that.$nodeTree.dynatree('getRoot').getChildren()[0];
				siteNode.fromDict({key: pageNodePath, title: pageTitle});
				that.refresh();
			});
		},

		pasteIsActive: function() {
			if (this.get('activeNode') && this.get('activeNode').data.nodeType === 'TYPO3.Neos:Page') {
				return false;
			}
			return this.get('cutNode') !== null || this.get('copiedNode') !== null;
		}.property('activeNode', 'cutNode', 'copiedNode'),

		currentFocusedNodeDoesNotAllowChildren: function() {
			return this.get('activeNode') && this.get('activeNode').data.nodeType === 'TYPO3.Neos:Page';
		}.property('activeNode'),

		isExpanded: function() {
			return NavigatePanelController.get('contextStructureMode');
		}.property('controller.contextStructureMode'),

		selectedNodeChanged: function() {
			if (!this.$nodeTree) {
				return;
			}
			var selectedNode = this.$nodeTree.dynatree('getTree').getNodeByKey(NodeSelection.get('selectedNode').$element.attr('about'));
			if (!selectedNode) {
				return;
			}
			selectedNode.activate();
			this.scrollToCurrentNode();
		}.observes('nodeSelection.selectedNode'),

		/**
		 * Initialize the dynatree instance stored at the DOM node
		 * this.$nodeTree
		 */
		_initializeTree: function() {
			if (this.$nodeTree) {
				return;
			}

			var page = InstanceWrapper.entities.get(InstanceWrapper.service('rdfa').getElementSubject(pageMetaInformation)),
				namespace = Configuration.get('TYPO3_NAMESPACE'),
				pageTitle = typeof page !== 'undefined' && typeof page.get(namespace + 'title') !== 'undefined' ? page.get(namespace + 'title') : this.pageNodePath;

			this.set('treeConfiguration', $.extend(true, this.get('treeConfiguration'), {
				parent: this,
				children: [
					{
						title: pageTitle,
						key: this.get('pageNodePath'),
						isFolder: true,
						expand: false,
						isLazy: true,
						select: false,
						active: false,
						unselectable: true,
						nodeType: 'TYPO3.Neos:Page',
						addClass: 'typo3-neos-page',
						iconClass: 'icon-sitemap'
					}
				],
				dnd: {
					/**
					 * sourceNode may be null for non-dynatree droppables.
					 * Return false to disallow dropping on node. In this case
					 * onDragOver and onDragLeave are not called.
					 * Return 'over', 'before, or 'after' to force a hitMode.
					 * Return ['before', 'after'] to restrict available hitModes.
					 * Any other return value will calc the hitMode from the cursor position.
					 */
					onDragEnter: function(node, sourceNode) {
						// It is only possible to move nodes into nodes of the nodeType ContentCollection
						if (node.data.nodeType === 'TYPO3.Neos:ContentCollection') {
							return ['before', 'after', 'over'];
						}
						else{
							return ['before', 'after'];
						}
					}
				},

				onClick: function(node, event) {
					if (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === null) {
						var nodePath = node.data.key,
							offsetFromTop = 150,
							$element = $('[about="' + nodePath + '"]');

						NodeSelection.updateSelection($element);
						$('html, body').animate({
							scrollTop: $element.offset().top - offsetFromTop
						}, 500);
					}
				},

				onDblClick: function(node, event) {
					event.preventDefault();
					return true;
				}
			}));

			this._super();

			this._initializePropertyObservers(pageMetaInformation);
		}
	});
});