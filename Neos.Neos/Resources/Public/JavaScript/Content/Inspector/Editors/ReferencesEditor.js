define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient',
		'Shared/NodeTypeService',
		'Shared/I18n',
		'Shared/Utility',
		'Library/sortable/Sortable'
	],
	function($, Ember, HttpRestClient, NodeTypeService, I18n, Utility, Sortable) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: '',
			_placeholder: function() {
				return I18n.translate(this.get('placeholder'), 'Type to search');
			}.property('placeholder'),

			// lazily initialized content – will be an array of select2 datums [{id: '12a9837…', text: 'My Node'}, …]:
			content: [],

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['Neos.Neos:Document'],

			// Minimum amount of characters to trigger search
			threshold: 2,

			// The path to a node that defines the starting point for the reference search
			startingPoint: null,

			didInsertElement: function() {
				var that = this,
					currentQueryTimer = null,
					select2Ul = null;

				if (this.get('startingPoint') === null || this.get('startingPoint') === '') {
					this.set('startingPoint', $('#neos-document-metadata').data('neos-site-node-context-path'));
				}

				this.$().select2({
					multiple: true,
					minimumInputLength: that.get('threshold'),
					placeholder: this.get('_placeholder'),
					formatResult: function(item, container, query, escapeMarkup) {
						var markup = [];
						Utility.Select2.util.markMatch(item.text, query.term, markup, escapeMarkup);
						var $itemContent = $('<span>' + markup.join('') + '</span>');

						var info = item.data.path ? item.data.path : item.data.identifier;
						$itemContent.attr('title', $itemContent.text().trim() + (info ? ' (' + info + ')' : ''));
						$itemContent.append('<span class="neos-select2-result-path">' + info + '</span>');

						var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui ? NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon : null;
						if (iconClass) {
							$itemContent.prepend('<i class="' + iconClass + '"></i>');
						}

						return $itemContent.get(0).outerHTML;
					},
					formatSelection: function(item) {
						var $itemContent = $('<span><em>' + I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...' + '</em></span>');

						if (item.data) {
							$itemContent = $('<span data-neos-identifier="' + item.data.identifier + '">' + item.text + '</span>');
							var info = item.data.path ? item.data.path : item.data.identifier;
							$itemContent.attr('title', $itemContent.text().trim() + (info ? ' (' + info + ')' : ''));

							var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui ? NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon : null;
							if (iconClass) {
								$itemContent.prepend('<i class="' + iconClass + '"></i>');
							}
						}

						return $itemContent.get(0).outerHTML;
					},
					query: function(query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function() {
							var parameters,
								$metadata = $('#neos-document-metadata');
							currentQueryTimer = null;

							parameters = {
								searchTerm: query.term,
								workspaceName: $metadata.data('neos-context-workspace-name'),
								dimensions: $metadata.data('neos-context-dimensions'),
								contextNode: that.get('startingPoint'),
								nodeTypes: that.get('nodeTypes')
							};

							HttpRestClient.getResource('neos-service-nodes', null, {data: parameters}).then(function(result) {
								var data = {results: []};
								$(result.resource).find('li').each(function(index, value) {
									var identifier = $('.node-identifier', value).text();

									data.results.push({
										id: identifier,
										text: $('.node-label', value).text().trim(),
										data: {identifier: identifier, path: Utility.removeContextPath($('.node-frontend-uri', this).text().trim().replace('.html', '')), nodeType: $('.node-type', value).text()}
									});
								});
								query.callback(data);
							});
						}, 200);
					}
				});

				this._makeSortable();
				this.$().select2('container').find('.neos-select2-input').attr('placeholder', this.get('_placeholder'));

				this.$().on('change', function() {
					that.set('content', $(this).select2('data'));
				});
			},

			_updateSelect2: function() {
				if (!this.$()) {
					return;
				}
				this.$().select2('data', this.get('content'));
			},

			_makeSortable: function() {
				var select2Ul, sortable, that = this;
				select2Ul = this.$().select2('container').find('ul.neos-select2-choices').first().addClass('neos-sortable');
				sortable = Sortable.create(select2Ul.get(0), {
					ghostClass: 'neos-sortable-ghost',
					chosenClass: 'neos-sortable-chosen',
					draggable: '.neos-select2-search-choice',
					onEnd: function(event) {
						that.$().select2('onSortEnd');
					}
				});
			},

			// actual value used and expected by the inspector:
			value: function(key, value) {
				var that = this,
					currentValue = JSON.stringify(this.get('content').map(function(item) {
						return item.id;
					}));

				if (value && value !== currentValue) {
					// Remove all items so they don't appear multiple times.
					that.set('content', []);
					that._updateSelect2();
					var nodeIdentifiers = JSON.parse(value);
					if (nodeIdentifiers.length > 0) {
						var parameters = {
							nodeIdentifiers: nodeIdentifiers,
							workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
							dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
						};
						HttpRestClient.getResource('neos-service-nodes', null, {data: parameters}).then(function(result) {
							$(result.resource).find('li').each(function(index, value) {
								var nodeIdentifier = $('.node-identifier', value).text().trim(),
									label = $('.node-label', value).text().trim(),
									icon = $('.node-icon', value).text().trim();
	
								var item = Ember.Object.extend({
									id: nodeIdentifier
								}).create();
	
								item.set('text', $('.node-label', value).text().trim());
								item.set('data', {identifier: $('.node-identifier', value).text(), path: $('.node-path', value).text(), nodeType: $('.node-type', value).text()});
	
								that.get('content').pushObject(item);
							});
							that._updateSelect2();
						});
					}
				}
				return currentValue;
			}.property('content.@each')
		});
	}
);
