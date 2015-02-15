define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient',
		'Shared/NodeTypeService'
	],
	function($, Ember, HttpRestClient, NodeTypeService) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: 'Type to search',

			content: null,

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['TYPO3.Neos:Document'],

			// Minimum amount of characters to trigger search
			threshold: 2,

			didInsertElement: function() {
				var that = this,
					currentQueryTimer = null;

				this.$().select2({
					multiple: true,
					maximumSelectionSize: 1,
					minimumInputLength: that.get('threshold'),
					placeholder: this.get('placeholder'),
					formatResult: function(item, container, query, escapeMarkup) {
						var markup = [];
						window.Select2.util.markMatch(item.text, query.term, markup, escapeMarkup);
						var $itemContent = $('<span>' + markup.join('') + '</span>');

						var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon;
						if (iconClass) {
							$itemContent.prepend('<i class="' + iconClass + '"></i>');
						}

						$itemContent.attr('title', item.data.path);

						return $itemContent.get(0).outerHTML;
					},
					formatSelection: function(item) {
						var $itemContent = $('<span>' + item.text + '</span>');

						if (item.data) {
							var iconClass = NodeTypeService.getNodeTypeDefinition(item.data.nodeType).ui.icon;
							if (iconClass) {
								$itemContent.prepend('<i class="' + iconClass + '"></i>');
							}

							$itemContent.attr('title', item.data.path);
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
								contextNode: $('#neos-document-metadata').data('neos-site-node-context-path'),
								nodeTypes: that.get('nodeTypes')
							};

							HttpRestClient.getResource('neos-service-nodes', null, {data: parameters}).then(function(result) {
								var data = {results: []};
								$(result.resource).find('li').each(function(index, value) {
									var identifier = $('.node-identifier', value).text();
									data.results.push({
										id: identifier,
										text: $('.node-label', value).text().trim(),
										data: {identifier: identifier, path: $('.node-path', value).text(), nodeType: $('.node-type', value).text()}
									});
								});
								query.callback(data);
							});
						}, 200);
					}
				});

				this.$().select2('container').find('.neos-select2-input').attr('placeholder', this.get('placeholder'));
				if (this.get('content')) {
					this.$().select2('container').find('.neos-select2-input').css({'display': 'none'});
				} else {
					this.$().select2('container').find('.neos-select2-input').css({'display': 'inline-block'});
				}

				this._updateSelect2();

				this.$().on('change', function() {
					var data = $(this).select2('data');
					if (data.length > 0) {
						that.set('content', data[0]);
						that.$().select2('container').find('.neos-select2-input').css({'display': 'none'});
					} else {
						that.set('content', '');
						that.$().select2('container').find('.neos-select2-input').css({'display': 'inline-block'});
					}
				});
			},

			// actual value used and expected by the inspector, in case of this Editor a string (node identifier):
			value: function(key, value) {
				var that = this;

				if (value && value !== this.get('content.id')) {
					var item = Ember.Object.extend({
						id: value,
						text: 'Loading ...'
					}).create();
					that.set('content', item);

					var parameters = {
						workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
						dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
					};
					HttpRestClient.getResource('neos-service-nodes', value, {data: parameters}).then(function(result) {
						item.set('text', $('.node-label', result.resource).text().trim());
						item.set('data', {identifier: $('.node-identifier', result.resource).text(), path: $('.node-path', result.resource).text(), nodeType: $('.node-type', result.resource).text()});
						that._updateSelect2();
					});

					that._updateSelect2();
				}
				return this.get('content.id') || '';
			}.property('content', 'content.id'),

			_updateSelect2: function() {
				if (!this.$()) {
					return;
				}
				if (this.get('content')) {
					this.$().select2('data', [this.get('content')]);
				} else {
					this.$().select2('data', []);
				}
			}
		});
	}
);
