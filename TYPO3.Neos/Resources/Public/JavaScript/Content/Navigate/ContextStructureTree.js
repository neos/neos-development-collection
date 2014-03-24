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
	'Shared/EventDispatcher',
	'Shared/NodeTypeService',
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
	EventDispatcher,
	NodeTypeService,
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
		refreshOnPageNodePathChanged: true,

		init: function() {
			this._super();
			this.set('insertNodePanel', InsertNodePanel.extend({baseNodeType: 'TYPO3.Neos:Content'}).create());
			var that = this;
			EventDispatcher.on('contentChanged', function() {
				that.refresh();
			});

			this.on('afterPageLoaded', function(){
				this._initializePropertyObservers($('#neos-page-metainformation'));
			});
		},

		_onPageNodePathChanged: function() {
			if (this.get('refreshOnPageNodePathChanged') === false) {
				this.set('refreshOnPageNodePathChanged', true);
				return;
			}

			var page = InstanceWrapper.entities.get(InstanceWrapper.service('rdfa').getElementSubject($('#neos-page-metainformation'))),
				namespace = Configuration.get('TYPO3_NAMESPACE'),
				pageTitle = typeof page !== 'undefined' && typeof page.get(namespace + 'title') !== 'undefined' ? page.get(namespace + 'title') : this.get('pageNodePath'),
				siteNode = this.$nodeTree.dynatree('getRoot').getChildren()[0];
			siteNode.fromDict({key: this.get('pageNodePath'), title: pageTitle});
			this.refresh();
		}.observes('pageNodePath'),

		pasteIsActive: function() {
			if (this.get('activeNode') && NodeTypeService.isOfType(this.get('activeNode').data.nodeType, 'TYPO3.Neos:Document')) {
				return false;
			}
			return this.get('cutNode') !== null || this.get('copiedNode') !== null;
		}.property('activeNode', 'cutNode', 'copiedNode'),

		currentFocusedNodeDoesNotAllowChildren: function() {
			return this.get('activeNode') && NodeTypeService.isOfType(this.get('activeNode').data.nodeType, 'TYPO3.Neos:Document');
		}.property('activeNode'),

		/**
		 * The condition on NodeType is to prevent modification of ContentCollections as the Workspaces module / publishing are not
		 * correctly handling that case and it can lead to broken rootlines if you just publish a node that
		 * is inside a moved Collection.
		 */
		currentFocusedNodeCanBeModified: function() {
			return (this.get('activeNode') && (this.get('activeNode').getLevel() <= this.unmodifiableLevels || NodeTypeService.isOfType(this.get('activeNode').data.nodeType, 'TYPO3.Neos:ContentCollection')));
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
						nodeType: 'TYPO3.Neos.NodeTypes:Page',
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
						if (NodeTypeService.isOfType(node.data.nodeType, 'TYPO3.Neos:ContentCollection')) {
							return ['before', 'after', 'over'];
						}
						else{
							return ['before', 'after'];
						}
					},

					onDragStart: function(node) {
						/**
						 * This is to prevent changing of ContentCollections as the Workspaces module / publishing are not
						 * correctly handling that case and it can lead to broken rootlines if you just publish a node that
						 * is inside a moved Collection.
						 */
						if (NodeTypeService.isOfType(node.data.nodeType, 'TYPO3.Neos:ContentCollection')) {
							return false;
						}

						return true;
					}
				},

				onClick: function(node, event) {
					if (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === null) {
						var nodePath = node.data.key,
							offsetFromTop = 150,
							$element = $('[about="' + nodePath + '"]');

						// Prevent errors if the element cannot be found on the page
						if ($element.length > 0) {
							NodeSelection.updateSelection($element);
							$('html, body').animate({
								scrollTop: $element.offset().top - offsetFromTop
							}, 500);
						}
					}
				},

				onDblClick: function(node, event) {
					event.preventDefault();
					return true;
				}
			}));

			this._super();

			this._initializePropertyObservers(pageMetaInformation);
		},

		afterDeleteNode: function() {
			this._doNotRefreshOnPageNodePathChanged();
			ContentModule.reloadPage();
		},

		afterPersistNode: function() {
			this._doNotRefreshOnPageNodePathChanged();
			ContentModule.reloadPage();
		},

		afterPaste: function() {
			this._doNotRefreshOnPageNodePathChanged();
			ContentModule.reloadPage();
		},

		afterMove: function() {
			this._doNotRefreshOnPageNodePathChanged();
			ContentModule.reloadPage();
		},

		_doNotRefreshOnPageNodePathChanged: function() {
			var that = this;
			ContentModule.one('pageLoaded', function() {
				that.set('refreshOnPageNodePathChanged', false);
			});
		}
	});
});
