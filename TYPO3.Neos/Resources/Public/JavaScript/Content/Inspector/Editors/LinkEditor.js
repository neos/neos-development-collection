define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpClient'
	],
	function($, Ember, HttpClient) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',

			content: null,

			nodesEndpoint: $('link[rel="neos-nodes"]').attr('href'),

			assetsEndpoint: $('link[rel="neos-assets"]').attr('href'),

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
					placeholder: 'Paste a link, or search',
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
							currentQueryTimer = null;

							var data = {results: []};

							if (that._isExternalUrl(query.term) || that._isValidEmailOrPhoneLink(query.term)) {
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
								Ember.RSVP.all([
									that._searchIn(query.term, that.nodesEndpoint, '&nodeTypes[]=' + that.get('nodeTypes').join('&nodeTypes[]=')),
									that._searchIn(query.term, that.assetsEndpoint, '')
								]).then(function (responses) {
									responses.forEach(function (parsedResponse) {
										$(parsedResponse).find('li').each(function (index, value) {
											var type = 'asset';
											if ($(value).data('path')) {
												type = 'node';
											}

											data.results.push({
												id: type + '://' + $(value).data('identifier'),
												text: $(value).text(),
												data: $(value).data(),
												'type': type
											});
										});
									});

									query.callback(data);
								});
							}

						}, 200);
					}
				});

				$(this.$().select2('container')).find('.neos-select2-input').attr('placeholder', 'Paste a link, or search');
				if (this.get('content')) {
					$(this.$().select2('container')).find('.neos-select2-input').css({'display' : 'none'});
				} else {
					$(this.$().select2('container')).find('.neos-select2-input').css({'display' : 'inline-block'});
				}

				this._updateSelect2();

				this.$().on('change', function() {
					var data = $(this).select2('data');
					if (data.length > 0) {
						that.set('content', data[0]);
						$(that.$().select2('container')).find('.neos-select2-input').css({'display' : 'none'});
					} else {
						that.set('content', '');
						$(that.$().select2('container')).find('.neos-select2-input').css({'display' : 'inline-block'});
					}
				});
			},

			// actual value used and expected by the inspector, in case of this Editor a string (node identifier):
			value: function(key, value) {
				var that = this,
					protocol = null;

				if (value) {
					var item = Ember.Object.extend({
						id: value,
						text: 'Loading ...',
						data: {}
					}).create();
					that.set('content', item);

					protocol = value.split(':', 1)[0];

					switch (protocol) {
						case 'node':
							this._findByIdentifier(value.substr(7, 36), this.nodesEndpoint).then(function(response) {
								item.set('text', $(response).filter('div').text());
								item.set('data', $(response).filter('div').data());
								that._updateSelect2();
							});
						break;
						case 'asset':
							this._findByIdentifier(value.substr(8, 36), this.assetsEndpoint).then(function (response) {
								item.set('text', $(response).filter('div').text());
								item.set('data', $(response).filter('div').data());
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
			},

			_isExternalUrl: function(value) {
				var regularExpression = /^([a-z]){3,10}:\/\/.{2,}$/;
				return regularExpression.test(value);
			},

			_isValidEmailOrPhoneLink: function(value) {
				var regularExpression =/^(mailto|tel):.{2,}$/;
				return regularExpression.test(value);
			},

			_findByIdentifier: function(identifier, endpointUrl) {
				return HttpClient.getResource(endpointUrl + '/' + identifier);
			},

			_searchIn: function(term, endpointUrl, additionalArguments) {
				var url = endpointUrl + '?searchTerm=' + term + additionalArguments;
				return HttpClient.getResource(url);
			}
		});
	}
);
