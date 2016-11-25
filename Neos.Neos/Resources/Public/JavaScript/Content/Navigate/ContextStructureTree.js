/**
 * Content structure tree
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./AbstractNodeTree',
	'Content/Application',
	'Shared/Configuration',
	'Shared/Notification',
	'Shared/EventDispatcher',
	'Shared/NodeTypeService',
	'../Model/NodeSelection',
	'../Model/PublishableNodes',
	'./NavigatePanelController',
	'Shared/I18n',
	'text!./ContextStructureTree.html'
], function(
	Ember,
	$,
	AbstractNodeTree,
	ContentModule,
	Configuration,
	Notification,
	EventDispatcher,
	NodeTypeService,
	NodeSelection,
	PublishableNodes,
	NavigatePanelController,
	I18n,
	template
) {
	var documentMetadata = $('#neos-document-metadata');

	return AbstractNodeTree.extend({
		elementId: ['neos-context-structure'],
		template: Ember.Handlebars.compile(template),
		controller: NavigatePanelController,
		nodeSelection: NodeSelection,
		baseNodeType: '!Neos.Neos:Document',
		treeSelector: '#neos-context-structure-tree',
		desiredNewPosition: 'inside',
		desiredPastePosition: 'inside',

		publishableNodes: PublishableNodes,

		_getAllowedChildNodeTypesForNode: function(node) {
			if (node.data.isAutoCreated) {
				var types = NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(node.parent.data.nodeType, node.data.name);
			} else {
				var types = NodeTypeService.getAllowedChildNodeTypes(node.data.nodeType);
			}

			if (types) {
				var contentTypes = NodeTypeService.getSubNodeTypes('Neos.Neos:Content'),
					contentTypesArray = Object.keys(contentTypes),
					filteredTypes = types.filter(function(n) {
						return contentTypesArray.indexOf(n) != -1;
					});
				return filteredTypes;
			} else {
				return [];
			}
		},

		init: function() {
			this._super();
			this.set('loadingDepth', Configuration.get('UserInterface.navigateComponent.structureTree.loadingDepth'));
			var that = this;
			EventDispatcher.on('contentChanged', function() {
				that.refresh();
			});

			this.on('afterPageLoaded', function() {
				this._initializePropertyObservers($('[about]'));
			});
		},

		_onPageNodePathChanged: function() {
			var documentMetadata = $('#neos-document-metadata'),
				page = NodeSelection.getNode(documentMetadata.attr('about')),
				documentNodeType = documentMetadata.data('node-_node-type'),
				nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(documentNodeType),
				siteNode = this.$nodeTree.dynatree('getRoot').getChildren()[0];
			siteNode.fromDict({
				key: this.get('pageNodePath'),
				title: page.get('nodeLabel'),
				nodeType: documentNodeType,
				nodeTypeLabel: nodeTypeConfiguration ? nodeTypeConfiguration.label : ''
			});
			this.refresh();
		}.observes('pageNodePath'),

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
			selectedNode.select();
			this.scrollToCurrentNode();
		}.observes('nodeSelection.selectedNode'),

		markDirtyNodes: function() {
			$('.neos-dynatree-dirty', this.$nodeTree).removeClass('neos-dynatree-dirty');

			var that = this;
			PublishableNodes.get('publishableEntitySubjects').forEach(function(entitySubject) {
				var treeNode = that.$nodeTree.dynatree('getTree').getNodeByKey(entitySubject.slice(1, -1));
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

			var page = NodeSelection.getNode(documentMetadata.attr('about')),
				nodeType = documentMetadata.data('node-_node-type'),
				nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType);

			this.set('treeConfiguration', $.extend(true, this.get('treeConfiguration'), {
				parent: this,
				children: [
					{
						title: page ? page.get('nodeLabel') : this.get('pageNodePath'),
						key: this.get('pageNodePath'),
						isFolder: true,
						expand: false,
						isLazy: true,
						select: true,
						active: false,
						unselectable: false,
						nodeType: nodeType,
						nodeTypeLabel: nodeTypeConfiguration ? nodeTypeConfiguration.label : '',
						addClass: 'typo3-neos-page',
						iconClass: 'icon-sitemap'
					}
				],

				onClick: function(node, event) {
					node.select();
					if (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === null) {
						this.options.parent._selectNode(node);
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
					$('a[title]', nodeSpan).tooltip({container: '#neos-application'});
				}
			}));

			this._super();

			this._initializePropertyObservers($('[about]'));
		},

		_selectNode: function(node) {
			var nodePath = node.data.key,
				$element = $('[about="' + nodePath + '"]');
			// Prevent errors if the element cannot be found on the page
			if ($element.length > 0) {
				NodeSelection.updateSelection($element, {scrollToElement: true, deselectEditables: true});
			}
		},

		afterDeleteNode: function() {
			ContentModule.reloadPage();
		},

		afterPersistNode: function(newNode) {
			this._selectElementAfterPageReload(newNode);
			ContentModule.reloadPage();
		},

		afterPaste: function(newNode) {
			this._selectElementAfterPageReload(newNode);
			ContentModule.reloadPage();
		},

		afterMove: function(newNode) {
			this._selectElementAfterPageReload(newNode);
			ContentModule.reloadPage();
		},

		_selectElementAfterPageReload: function(newNode) {
			var that = this;
			ContentModule.one('pageLoaded', function() {
				newNode.focus();
				that._selectNode(newNode);
			});
		},

		afterLoadNode: function(node) {
			if (node.getLevel() === 1) {
				var tree = this.$nodeTree.dynatree('getTree'),
					currentNode = tree.getNodeByKey(NodeSelection.get('selectedNode').$element.attr('about'));
				if (currentNode) {
					currentNode.activate();
					currentNode.select();
					this.scrollToCurrentNode();
				}
			}
		}
	});
});
