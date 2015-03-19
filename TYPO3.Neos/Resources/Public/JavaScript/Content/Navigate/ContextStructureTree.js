/**
 * Content structure tree
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./AbstractNodeTree',
	'Content/Application',
	'Content/Model/Node',
	'vie',
	'Shared/Configuration',
	'Shared/Notification',
	'Shared/EventDispatcher',
	'Shared/NodeTypeService',
	'../Model/NodeSelection',
	'../Model/PublishableNodes',
	'./NavigatePanelController',
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
	PublishableNodes,
	NavigatePanelController,
	template
) {
	var documentMetadata = $('#neos-document-metadata');

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
		desiredNewPosition: 'inside',
		desiredPastePosition: 'inside',

		publishableNodes: PublishableNodes,

		init: function() {
			this._super();
			var that = this;
			EventDispatcher.on('contentChanged', function() {
				that.refresh();
			});

			this.on('afterPageLoaded', function(){
				this._initializePropertyObservers($('#neos-document-metadata'));
			});
		},

		_onPageNodePathChanged: function() {
			if (this.get('refreshOnPageNodePathChanged') === false) {
				this.set('refreshOnPageNodePathChanged', true);
				return;
			}

			var page = InstanceWrapper.entities.get(InstanceWrapper.service('rdfa').getElementSubject($('#neos-document-metadata'))),
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

		markDirtyNodes: function() {
			$('.neos-dynatree-dirty', this.$nodeTree).removeClass('neos-dynatree-dirty');

			var that = this;
			PublishableNodes.get('publishableEntitySubjects').forEach(function(entitySubject) {
				var treeNode = that.$nodeTree.dynatree('getTree').getNodeByKey(entitySubject.slice(1, entitySubject.length - 1));
				if (treeNode) {
					$(treeNode.span).addClass('neos-dynatree-dirty');
				}
			});
		}.observes('publishableNodes.publishableEntitySubjects'),

		/**
		 * Initialize the dynatree instance stored at the DOM node
		 * this.$nodeTree
		 */
		_initializeTree: function() {
			if (this.$nodeTree) {
				return;
			}

			var page = InstanceWrapper.entities.get(InstanceWrapper.service('rdfa').getElementSubject(documentMetadata)),
				namespace = Configuration.get('TYPO3_NAMESPACE'),
				pageTitle = typeof page !== 'undefined' && typeof page.get(namespace + 'title') !== 'undefined' ? page.get(namespace + 'title') : this.pageNodePath,
				documentNodeType = (page ? page.get('typo3:_nodeType'): 'TYPO3.Neos.NodeTypes:Page'); // TODO: This fallback to TYPO3.Neos.NodeTypes:Page should go away, but currently in some rare cases "page" is not yet initialized. In order to fix this loading order issue, we need to re-structure the tree, though.

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
						nodeType: documentNodeType,
						addClass: 'typo3-neos-page',
						iconClass: 'icon-sitemap'
					}
				],

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
				},

				onRender: function(node, nodeSpan) {
					if (PublishableNodes.get('publishableEntitySubjects').indexOf('<' + node.data.key + '>') !== -1) {
						$(nodeSpan).addClass('neos-dynatree-dirty');
					}
				}
			}));

			this._super();

			this._initializePropertyObservers(documentMetadata);
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
