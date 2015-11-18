define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient',
		'Shared/NodeTypeService',
		'Shared/I18n',
		'Shared/Utility'
	],
	function($, Ember, HttpRestClient, NodeTypeService, I18n, Utility) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: '',
			_placeholder: function () {
				return I18n.translate(this.get('placeholder'), 'Type to search');
			}.property('placeholder'),

			// lazily initialized content – will be an array of select2 datums [{id: '12a9837…', text: 'My Node'}, …]:
			content: [],

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['TYPO3.Neos:Document'],

			// Minimum amount of characters to trigger search
			threshold: 2,

			// The path to a node that defines the starting point for the reference search
			startingPoint: null,

			didInsertElement: function() {
				var that = this,
					currentQueryTimer = null;

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

						var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon;
						if (iconClass) {
							$itemContent.prepend('<i class="' + iconClass + '"></i>');
						}

						return $itemContent.get(0).outerHTML;
					},
					formatSelection: function(item) {
						var $itemContent = $('<span>' + item.text + '</span>');

						if (item.data) {
							var info = item.data.path ? item.data.path : item.data.identifier;
							$itemContent.attr('title', $itemContent.text().trim() + (info ? ' (' + info + ')' : ''));

							var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon;
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
							currentQueryTimer = null;

							var parameters = {
								searchTerm: query.term,
								workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
								dimensions: $('#neos-document-metadata').data('neos-context-dimensions'),
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

			// actual value used and expected by the inspector:
			value: function(key, value) {
				var that = this,
					currentValue = JSON.stringify(this.get('content').map(function(item) {
						return item.id;
					}));

				if (value && value !== currentValue) {
					// Remove all items so they don't appear multiple times.
					// TODO: cache already found items and load multiple node records at once
					that.set('content', []);
					// load names of already selected nodes via the Node REST service:
					$(JSON.parse(value)).each(function(index, nodeIdentifier) {
						var item = Ember.Object.extend({
							id: nodeIdentifier,
							text: function() {
								return I18n.translate('TYPO3.Neos:Main:loading', 'Loading') + ' ...';
							}.property()
						}).create();

						that.get('content').pushObject(item);

						var parameters = {
							workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
							dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
						};
						HttpRestClient.getResource('neos-service-nodes', nodeIdentifier, {data: parameters}).then(function(result) {
							item.set('text', $('.node-label', result.resource).text().trim());
							item.set('data', {identifier: $('.node-identifier', result.resource).text(), path: Utility.removeContextPath($('.node-frontend-uri', result.resource).text().trim().replace('.html', '')), nodeType: $('.node-type', result.resource).text()});
							that._updateSelect2();
						});

					});
					that._updateSelect2();
				}
				return currentValue;
			}.property('content.@each')
		});
	}
);
