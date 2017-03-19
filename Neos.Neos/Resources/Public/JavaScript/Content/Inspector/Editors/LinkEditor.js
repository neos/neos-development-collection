define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient',
		'Shared/Utility',
		'Shared/NodeTypeService',
		'Shared/I18n'
	],
	function(
		$,
		Ember,
		HttpRestClient,
		Utility,
		NodeTypeService,
		I18n
	) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: null,
			_placeholder: function() {
				return I18n.translate(this.get('placeholder'), 'Paste a link, or type to search');
			}.property('placeholder'),

			content: null,
			searchRequest: null,

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['Neos.Neos:Document'],
			assets: true,

			didInsertElement: function() {
				var that = this,
					currentQueryTimer;

				this.$().select2({
					minimumInputLength: 2,
					maximumSelectionSize: 1,
					multiple: true,
					placeholder: this.get('_placeholder'),
					escapeMarkup: function(markup) {
						return markup;
					},
					formatResult: function(item, container, query, escapeMarkup) {
						var info = item.data.path ? item.data.path : item.data.identifier;
						var markup = [];
						var $itemContent;

						Utility.Select2.util.markMatch(item.text, query.term, markup, escapeMarkup);
						$itemContent = $('<span>' + markup.join('') + '</span>');

						$itemContent.attr('title', $itemContent.text().trim() + (info ? ' (' + info + ')' : ''));
						$itemContent.append('<span class="neos-select2-result-path">' + info + '</span>');

						if (item.data.icon) {
							$itemContent.prepend('<i class="' + item.data.icon + '"></i>');
						}

						if (item.data.thumbnail) {
							$itemContent.prepend($('<img />').attr({src: item.data.thumbnail, alt: item.text}));
						}

						return $itemContent.get(0).outerHTML;
					},
					formatSelection: function(item) {
						var info = item.data.path ? item.data.path : item.data.identifier;
						var $itemContent = $('<span>' + item.text + '</span>');

						$itemContent.attr('title', $itemContent.text().trim() + (info ? ' (' + info + ')' : ''));

						if (item.data.icon) {
							$itemContent.prepend('<i class="' + item.data.icon + '"></i>');
						}

						return $itemContent.get(0).outerHTML;
					},
					dropdownCssClass: 'neos',
					query: function(query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function() {
							currentQueryTimer = null;

							if (Utility.isValidLink(query.term)) {
								var results = [];
								results.push({
									id: query.term,
									text: query.term,
									data: {
										icon: 'icon-link'
									},
									'type': 'external'
								});
								query.callback({results: results});
							} else {
								var requests = [];
								if (that.get('nodeTypes')) {
									requests.push(HttpRestClient.getResource('neos-service-nodes', null, {data: {
										workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
										dimensions: $('#neos-document-metadata').data('neos-context-dimensions'),
										contextNode: $('#neos-document-metadata').data('neos-site-node-context-path'),
										searchTerm: query.term,
										nodeTypes: that.get('nodeTypes')
									}}));
								}

								if (that.get('assets')) {
									requests.push(HttpRestClient.getResource('neos-service-assets', null, {data: {searchTerm: query.term}}));
								}

								Ember.RSVP.all(requests).then(function(results) {
									var nodes = [],
										assets = [];
									results.forEach(function(result) {
										$(result.resource).find('li').each(function(index, value) {
											if ($(value).hasClass('node')) {
												var iconClass = NodeTypeService.getNodeTypeDefinition($('.node-type', value).text()).ui.icon;
												nodes.push({
													id: 'node://' + $('.node-identifier', value).text(),
													text: $('.node-label', value).text().trim(),
													data: {icon: iconClass, path: Utility.removeContextPath($('.node-frontend-uri', this).text().trim())},
													'type': 'node'
												});
											} else {
												var identifier = $('.asset-identifier', value).text();
												assets.push({
													id: 'asset://' + identifier,
													text: $('.asset-label', value).text().trim(),
													data: {identifier: identifier, thumbnail: $('[rel="thumbnail"]', value).attr('href')},
													'type': 'asset'
												});
											}
										});
									});
									query.callback({results: nodes.concat(assets)});
								});
							}
						}, 200);
					}
				});

				var $input = this.$().select2('container').find('.neos-select2-input'),
					parseLink = function() {
						var value = $input.val();
						if (!Utility.isValidLink(value) && value.indexOf('.') > -1) {
							var url = 'http://' + value;
							that.$().select2('data', {
								id: url,
								text: url,
								data: {
									icon: 'icon-link'
								},
								'type': 'external'
							}, true);
						}
					};
				$input
					.attr('placeholder', this.get('_placeholder'))
					.css({'display': this.get('content') ? 'none' : 'inline-block'})
					.on('keyup', function(e) {
						if (e.keyCode === 13) {
							parseLink();
						}
					});
				this._updateSelect2();

				this.$().on('change', function() {
					var data = $(this).select2('data');
					that.set('content', data[0] || '');
					$input.css({'display': data.length > 0 ? 'none' : 'inline-block'});
				});
			},

			valueDidChange: function() {
				if (this.$()) {
					this.$().select2('container').find('.neos-select2-input').css({'display': this.get('value').length > 0 ? 'none' : 'inline-block'});
					this._updateSelect2();
				}
			}.observes('value'),

			// actual value used and expected by the inspector, in case of this Editor a string (node identifier):
			value: function(key, value) {
				var parameters, nodeIdentifier, item, that, protocol;

				if (value) {
					that = this;
					item = Ember.Object.extend({
						id: value,
						text: function() {
							return I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...';
						}.property(),
						data: {}
					}).create();
					that.set('content', item);

					protocol = value.split(':', 1)[0];

					switch (protocol) {
						case 'node':
							nodeIdentifier = value.substr(7, 255);
							parameters = {
								workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
								dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
							};
							HttpRestClient.getResource('neos-service-nodes', nodeIdentifier, {data: parameters}).then(
								function(result) {
									var iconClass = NodeTypeService.getNodeTypeDefinition($('.node-type', result.resource).text()).ui.icon;
									item.set('text', $('.node-label', result.resource).text().trim());
									item.set('data', {icon: iconClass, path: Utility.removeContextPath($('.node-frontend-uri', result.resource).text().trim())});
									that._updateSelect2();
								},
								function() {
									item.set('text', '<i> Node missing</i>');
									item.set('data', {icon: 'icon-warning-sign', identifier: nodeIdentifier});
									that._updateSelect2();
								}
							);
						break;
						case 'asset':
							var assetIdentifier = value.substr(8, 36);
							HttpRestClient.getResource('neos-service-assets', assetIdentifier).then(
								function(result) {
									item.set('text', $('.asset-label', result.resource).text().trim());
									item.set('data', {icon: 'icon-file-alt', identifier: $('.asset-identifier', result.resource).text()});
									that._updateSelect2();
								},
								function() {
									item.set('text', '<i> Asset missing</i>');
									item.set('data', {icon: 'icon-warning-sign', identifier: assetIdentifier});
									that._updateSelect2();
								}
							);
						break;
						default:
							item.set('text', value);
							item.set('data', {icon: 'icon-link'});
						break;
					}
				} else if (value === '') {
					this.set('content', null);
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
