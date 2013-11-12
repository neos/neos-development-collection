define(
	[
		'Library/jquery-with-dependencies',
		'emberjs'
	],
	function($, Ember) {
		return Ember.View.extend({

			tagName: 'input',
			attributeBindings: ['type'],
			type: 'hidden',

			content: null,

			// array of allowed node type names, configured via editorOptions:
			nodeTypes: function() {
				return ['TYPO3.Neos:Document'];
			}.property(),

			didInsertElement: function() {
				var that = this;
				var nodesEndpoint = $('link[type="application/vnd.typo3.neos.nodes"]').attr('href');

				var currentQueryTimer = null;
				this.$().select2({
					minimumInputLength: 1,
					maximumSelectionSize: 1,
					multiple: true,
					placeholder: 'Select ...',
					query: function (query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function() {
							currentQueryTimer = null;

							$.get(nodesEndpoint + '?searchTerm=' + query.term + that.get('nodeTypes').reduce(function(previousValue, item) {
								return previousValue + '&nodeTypes[]=' + item;
							}, ''), function(parsedResponse) {
								var data = {results: []};

								$(parsedResponse).find('li').each(function(index, value){
									data.results.push({
										id: $(value).data('identifier'),
										text:  $(value).text()
									});
								});

								query.callback(data);
							}, 'html');
						}, 200);
					}
				});

				$(this.$().select2('container')).find('.neos-select2-input').attr('placeholder', 'Type to Search');
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
			_updateSelect2: function() {
				if (!this.$()) {
					return;
				}
				this.$().select2('data', this.get('content'));
			},

			// actual value used and expected by the inspector, in case of this Editor a string (node identifier):
			value: function(key, value) {
				var that = this;

				if (value) {
					var nodesEndpoint = $('link[type="application/vnd.typo3.neos.nodes"]').attr('href');

					var item = Ember.Object.extend({
						id: value,
						text: 'Loading ...'
					}).create();
					that.set('content', item);
					$.ajax(nodesEndpoint + '/' + value).done(function(response) {
						item.set('text', $(response).filter('div').text());
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