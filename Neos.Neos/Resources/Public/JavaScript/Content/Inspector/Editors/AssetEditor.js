define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Content/Inspector/Editors/FileUpload',
	'text!./AssetEditor.html',
	'Content/Inspector/SecondaryInspectorController',
	'Shared/Utility',
	'Shared/HttpClient',
	'Shared/I18n',
	'Library/sortable/Sortable'
],
function(Ember, $, FileUpload, template, SecondaryInspectorController, Utility, HttpClient, I18n, Sortable) {

	return FileUpload.extend({
		removeButtonLabel: function() {
			return I18n.translate('Neos.Neos:Main:remove', 'Remove')
		}.property(),
		template: Ember.Handlebars.compile(template),
		SecondaryInspectorButton: SecondaryInspectorController.SecondaryInspectorButton,
		assets: [],

		/**
		 * Whether to enable editing of multiple Assets
		 */
		multiple: false,

		_assetMetadataEndpointUri: null,

		_showLoadingIndicator: false,

		init: function() {
			this._super();

			// Create new instance per asset editor to avoid side effects
			this.set('_mediaBrowserView', Ember.View.extend({
				template: Ember.Handlebars.compile('<iframe style="width:100%; height: 100%" src="' + $('link[rel="neos-media-browser"]').attr('href') + '"></iframe>'),
				didInsertElement: function() {
					this.$().find('iframe').on('load', function(event) {
						if (window.NeosMediaBrowserCallbacks && window.NeosMediaBrowserCallbacks.onLoad) {
							window.NeosMediaBrowserCallbacks.onLoad(event);
						}
					});
				}
			}));

			this.set('assets', Ember.A());
			this.set('_assetMetadataEndpointUri', $('link[rel="neos-asset-metadata"]').attr('href'));
		},

		didInsertElement: function() {
			this._super();

			if (!this.get('loadingLabel')) {
				this.set('loadingLabel', I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...');
			}

			if (this.get('multiple') === true) {
				this._makeSortable();
			}
		},

		/**
		 * Observe value to initialize list
		 */
		_valueDidChange: function() {
			if (JSON.stringify(this.get('assets').mapBy('assetUuid')) !== this.get('value')) {
				this.set('assets', Ember.A());
				this._readAndDeserializeValue();
			}
		}.observes('value'),

		_makeSortable: function() {
			var itemList, sortable, that = this;
			itemList = this.$().find('ul.neos-inspector-file-list').first().addClass('neos-sortable');
			sortable = Sortable.create(itemList.get(0), {
				ghostClass: 'neos-sortable-ghost',
				chosenClass: 'neos-sortable-chosen',
				onUpdate: function(event) {
					var data = [];
					itemList.find('li').each(function() {
						var currentIdentifier = $(this).find('[data-neos-identifier]').data('neos-identifier');
						$(that.get('assets')).each(function() {
							if (!this.assetUuid) {
								return;
							}
							if (this.assetUuid === currentIdentifier) {
								data.push(this);
							}
						});
					});
					that.set('assets', data);
					that._updateValue();
				}
			});
		},

		_loadingLabel: function() {
			if (this.get('_showLoadingIndicator') === true) {
				return this.get('loadingLabel');
			}
			return '';
		}.property('_showLoadingIndicator'),

		assetView: Ember.CollectionView.extend({
			tagName: 'ul',
			classNames: ['neos-inspector-file-list'],
			itemViewClass: Ember.View.extend({
				template: Ember.Handlebars.compile('<span data-neos-identifier="{{unbound view.content.assetUuid}}"><img src="{{unbound view.content.previewImageResourceUri}}" {{bind-attr alt="view.content.filename"}} /></span>{{view.content.filename}} <span class="neos-button neos-asset-editor-remove" {{action remove target="view"}}></span>'),
				attributeBindings: ['title'],
				titleBinding: 'content.filename',
				actions: {
					remove: function() {
						this.get('_parentView._parentView').removeAsset(this.get('content'));
					}
				}
			}),
			emptyView: Ember.View.extend({
				template: Ember.Handlebars.compile('{{view._parentView._parentView._loadingLabel}}'),
				tagName: 'span',
				classNames: ['neos-inspector-file-loading']
			})
		}),

		/**
		 * On startup, we deserialize the JSON string and fill the "assets" property
		 */
		_readAndDeserializeValue: function() {
			var value = this.get('value');

			if (!value || !Utility.isValidJsonString(value)) {
				return;
			}

			var that = this;

			var assetIdentifiers = JSON.parse(value);
			if (!this.multiple) {
				assetIdentifiers = assetIdentifiers !== null ? [assetIdentifiers] : [];
			}

			if (assetIdentifiers.length > 0) {
				this.set('_showLoadingIndicator', true);
				HttpClient.getResource(that.get('_assetMetadataEndpointUri') + '?' + $.param({assets: assetIdentifiers})).then(
					function(result) {
						that.get('assets').addObjects(result);
						that.set('_showLoadingIndicator', false);
					}
				);
			}
		},

		/****************************************
		 * MEDIA BROWSER
		 ***************************************/
		_mediaBrowserView: null,

		_beforeMediaBrowserIsShown: function() {
			var that = this;
			window.NeosMediaBrowserCallbacks = {
				assetChosen: function(assetIdentifier) {
					// we hide the default upload preview image; as we only want the loading indicator to be visible
					that.set('_loadPreviewImageHandler', HttpClient.getResource(
						that.get('_assetMetadataEndpointUri') + '?assets[]=' + assetIdentifier,
						{dataType: 'json'}
					));
					that.get('_loadPreviewImageHandler').then(function(result) {
						that.fileUploaded(result[0]);
					});
					that.set('mediaBrowserShown', false);
				}
			};
		},

		/****************************************
		 * FILE REMOVE
		 ***************************************/
		removeAsset: function(asset) {
			this.get('assets').removeObject(this.get('assets').findProperty('assetUuid', asset.assetUuid));
			this._updateValue();
		},

		/**
		 * Callback after file upload is complete
		 */
		fileUploaded: function(asset) {
			this._super();

			// if used directly as callback for in filesScheduledForUpload and not in the media
			// browser via getJSON() a string will be handed over.
			if (typeof asset === 'string') {
				asset = $.parseJSON(asset);
			}

			// Replace existing assets if we don't allow editing of multiple assets
			if (!this.multiple) {
				this.get('assets').setObjects([]);
			}
			this.get('assets').pushObject(asset);
			this._updateValue();
		},

		/****************************************
		 * Saving / Loading
		 ***************************************/
		/**
		 * This function must be triggered *explicitly* when
		 * the assets collection changes as this function
		 * writes these changes back into a JSON object.
		 *
		 * We don't use value observing here, as this might end up with a circular
		 * dependency.
		 */
		_updateValue: function() {
			var collectedValues = this.get('assets').mapBy('assetUuid');
			if (this.multiple) {
				this.set('value', JSON.stringify(collectedValues));
			} else {
				this.set('value', JSON.stringify(collectedValues.length > 0 ? collectedValues[0] : null));
			}
		},

		_initializeUploader: function() {
			this._super();

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['metadata'] = 'Asset';
			});
		}
	});
});
