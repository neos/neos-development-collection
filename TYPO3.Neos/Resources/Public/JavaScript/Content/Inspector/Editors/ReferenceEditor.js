define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Shared/HttpRestClient'
	],
	function($, Ember, HttpRestClient) {
		return Ember.View.extend({
			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',
			placeholder: 'Type to search',

			content: null,

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['TYPO3.Neos:Document'],

			didInsertElement: function() {
				var that = this,
					currentQueryTimer = null;

				this.$().select2({
					minimumInputLength: 1,
					maximumSelectionSize: 1,
					multiple: true,
					placeholder: this.get('placeholder'),

					query: function (query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function() {
							currentQueryTimer = null;

							var arguments = {
								workspaceName: $('#neos-document-metadata').attr('data-context-__workspacename'),
								searchTerm: query.term,
								nodeTypes: that.get('nodeTypes')
							};

							HttpRestClient.getResource('neos-service-nodes', null, {data: arguments}).then(function(result) {
								var data = {results: []};
								$(result.resource).find('li').each(function(index, value) {
									data.results.push({
										id: $('.node-identifier', value).text(),
										text: $('.node-label', value).text()
									});
								});
								query.callback(data);
							});
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
				var that = this;

				if (value) {
					var item = Ember.Object.extend({
						id: value,
						text: 'Loading ...'
					}).create();
					that.set('content', item);

					var arguments = { workspaceName: $('#neos-document-metadata').attr('data-context-__workspacename') };
					HttpRestClient.getResource('neos-service-nodes', value, {data: arguments}).then(function(result) {
						item.set('text', $('.node-label', result.resource).text());
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
