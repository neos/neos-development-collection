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

			// lazily initialized content – will be an array of select2 datums [{id: '12a9837…', text: 'My Node'}, …]:
			content: [],

			// array of allowed node type names, configurable via editorOptions
			nodeTypes: ['TYPO3.Neos:Document'],

			didInsertElement: function() {
				var that = this;
				var nodesEndpoint = $('link[rel="neos-nodes"]').attr('href');

				var currentQueryTimer = null;
				this.$().select2({
					multiple: true,
					minimumInputLength: 3,
					query: function (query) {
						if (currentQueryTimer) {
							window.clearTimeout(currentQueryTimer);
						}
						currentQueryTimer = window.setTimeout(function() {
							currentQueryTimer = null;

							var url = nodesEndpoint + '?searchTerm=' + query.term + '&nodeTypes[]=' + that.get('nodeTypes').join('&nodeTypes[]=');
							HttpClient.getResource(url).then(
								function(parsedResponse) {
									var data = {results: []};

									$(parsedResponse).find('li').each(function(index, value){
										data.results.push({
											id: $(value).data('identifier'),
											text:  $(value).text()
										});
									});

									query.callback(data);
								}
							);
						}, 200);
					}
				});

				$(this.$().select2('container')).find('.neos-select2-input').attr('placeholder', 'Type to Search');

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
				var that = this;

				if (value) {
					var nodesEndpoint = $('link[rel="neos-nodes"]').attr('href');

					// Remove all items so they don't appear multiple times.
					// TODO: cache already found items and load multiple node records at once
					that.set('content', []);
					// load names of already selected nodes via the Node REST service:
					$(JSON.parse(value)).each(function(index, nodeIdentifier) {

						var item = Ember.Object.extend({
							id: nodeIdentifier,
							text: 'Loading ...'
						}).create();

						that.get('content').pushObject(item);

						HttpClient.getResource(nodesEndpoint + '/' + nodeIdentifier).then(
							function(response) {
								item.set('text', $(response).filter('div').text());
								that._updateSelect2();
							}
						);
					});
					that._updateSelect2();
				}
				return JSON.stringify(this.get('content').map(function(item){
					return item.id;
				}));
			}.property('content.@each')
		});
	}
);
