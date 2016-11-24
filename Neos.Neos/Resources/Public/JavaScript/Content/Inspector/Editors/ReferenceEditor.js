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

			content: null,

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['Neos.Neos:Document'],

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
					maximumSelectionSize: 1,
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
						var $itemContent = $('<span>' + item.text + '</span>');

						if (item.data) {
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
							var parameters;
							var $metadata = $('#neos-document-metadata');
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

				this.$().select2('container').find('.neos-select2-input').attr('placeholder', this.get('_placeholder'));
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
				var parameters;
				var $metadata = $('#neos-document-metadata');

				if (value && value !== this.get('content.id')) {
					var item = Ember.Object.extend({
						id: value,
						text: function() {
							return I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...';
						}.property()
					}).create();
					that.set('content', item);

					parameters = {
						workspaceName: $metadata.data('neos-context-workspace-name'),
						dimensions: $metadata.data('neos-context-dimensions')
					};
					HttpRestClient.getResource('neos-service-nodes', value, {data: parameters}).then(function(result) {
						item.set('text', $('.node-label', result.resource).text().trim());
						item.set('data', {identifier: $('.node-identifier', result.resource).text(), path: Utility.removeContextPath($('.node-frontend-uri', result.resource).text().trim().replace('.html', '')), nodeType: $('.node-type', result.resource).text()});
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
