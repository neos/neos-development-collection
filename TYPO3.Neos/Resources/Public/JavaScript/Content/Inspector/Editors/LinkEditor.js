define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient',
		'Shared/Utility'
	],
	function(
		$,
		Ember,
		HttpRestClient,
		Utility
	) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: 'Paste a link, or type to search',

			content: null,

			searchRequest: null,

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['TYPO3.Neos:Document'],

			didInsertElement: function() {
				var that = this,
					currentQueryTimer;

				this.$().select2({
					minimumInputLength: 2,
					maximumSelectionSize: 1,
					multiple: true,
					placeholder: this.get('placeholder'),
					escapeMarkup: function (markup) {
						return markup;
					},
					formatResult: function(item) {
						var itemContent = '';

						itemContent += '<span>';

						if (item.data.icon) {
							itemContent += '<i class="' + item.data.icon + '"></i> ';
						}

						itemContent += '<b>' + item.text + '</b>';

						if (item.data.path) {
							itemContent += '<br />' + item.data.path;
						} else if (item.data.identifier) {
							itemContent += '<br />' + item.data.identifier;
						}

						itemContent += '</span>';

						return itemContent;
					},
					formatSelection: function(item) {
						var itemContent = '';

						itemContent += '<span>';

						if (item.data.icon) {
							itemContent += '<i class="' + item.data.icon + '"></i> ';
						}

						itemContent += '<b>' + item.text + '</b>';

						if (item.data.path) {
							itemContent += '<br />' + item.data.path;
						} else if (item.data.identifier) {
							itemContent += '<br />' + item.data.identifier;
						}

						itemContent += '</span>';

						return itemContent;
					},
					dropdownCssClass: 'neos',
					query: function (query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function () {
							var data, parameters;
							currentQueryTimer = null;

							data = {results: []};

							if (Utility.isValidLink(query.term)) {
								data.results.push({
									id: query.term,
									text: query.term,
									data: {
										icon: 'icon-link'
									},
									'type': 'external'
								});
								query.callback(data);
							} else {
								parameters = {
									workspaceName: $('#neos-page-metainformation').attr('data-context-__workspacename'),
									searchTerm: query.term,
									nodeTypes: that.get('nodeTypes')
								};

								HttpRestClient.getResource('neos-service-nodes', null, {data: parameters}).then(function (result) {
									$(result.resource).find('li').each(function (index, value) {
										data.results.push({
											id: 'node://' + $('.node-identifier', value).text(),
											text: $('.node-label', value).text(),
											data: $(value).data(),
											'type': 'node'
										});
										query.callback(data);
									});
								});

								parameters = {
									searchTerm: query.term
								};

								HttpRestClient.getResource('neos-service-assets', null, {data: parameters}).then(function (result) {
									data = {results: []};
									$(result.resource).find('li').each(function (index, value) {
										data.results.push({
											id: 'asset://' + $('.asset-identifier', value).text(),
											text: $('.asset-label', value).text(),
											data: $(value).data(),
											'type': 'asset'
										});
									});
								});
							}
						}, 200);
					}
				});

				this.$().select2('container').find('.neos-select2-input').attr('placeholder', this.get('placeholder'));
				if (this.get('content')) {
					this.$().select2('container').find('.neos-select2-input').css({'display' : 'none'});
				} else {
					this.$().select2('container').find('.neos-select2-input').css({'display' : 'inline-block'});
				}

				this._updateSelect2();

				this.$().on('change', function() {
					var data = $(this).select2('data');
					if (data.length > 0) {
						that.set('content', data[0]);
						that.$().select2('container').find('.neos-select2-input').css({'display' : 'none'});
					} else {
						that.set('content', '');
						that.$().select2('container').find('.neos-select2-input').css({'display' : 'inline-block'});
					}
				});
			},

			// actual value used and expected by the inspector, in case of this Editor a string (node identifier):
			value: function(key, value) {
				var parameters, nodeIdentifier, item, that, protocol;

				if (value) {
					that = this;
					item = Ember.Object.extend({
						id: value,
						text: 'Loading ...',
						data: {}
					}).create();
					that.set('content', item);

					protocol = value.split(':', 1)[0];

					switch (protocol) {
						case 'node':
							nodeIdentifier = value.substr(7, 36);
							parameters = {
								workspaceName: $('#neos-page-metainformation').attr('data-context-__workspacename')
							};
							HttpRestClient.getResource('neos-service-nodes', nodeIdentifier, {data: parameters}).then(function(result) {
								item.set('text', $('.node-label', result.resource).text());
								item.set('data', $('.node-data', result.resource).text());
								that._updateSelect2();
							});
						break;
						case 'asset':
							var assetIdentifier = value.substr(8, 36);
							HttpRestClient.getResource('neos-service-assets', assetIdentifier).then(function(result) {
								item.set('text', $('.asset-label', result.resource).text());
								item.set('data', $('.asset-data', result.resource).text());
								that._updateSelect2();
							});
						break;
						default:
							item.set('text', value);
							that._updateSelect2();
						break;
					}
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
