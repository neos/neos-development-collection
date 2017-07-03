define(
	[
		'Library/jquery-with-dependencies',
		'vie',
		'emberjs',
		'Content/InputEvents/EntitySelection',
		'Content/Model/NodeSelection',
		'Content/EditPreviewPanel/EditPreviewPanelController',
		'Library/create',
		'InlineEditing/CreateJs/jquery.typo3.collectionWidget',
		'aloha'
	],
	function(
		$,
		vieInstance,
		Ember,
		EntitySelection,
		NodeSelection,
		EditPreviewPanelController
	) {
		return Ember.Object.create({
				// Initially set state to null
			_state: null,
			editableOptions: {
				disabled: false,
				vie: vieInstance,
				widgets: {
					'default': 'aloha'
				},
				collectionWidgets: {
					'default': 'typo3CollectionWidget'
				},
				editors: {
					aloha: {
						widget: 'alohaWidget'
					},
					'inline-only': {
						widget: 'editWidget'
					}
				}
			},

			initialize: function() {
				var that = this;

					// Wait until Aloha is loaded if we use Aloha
				if (Aloha.__shouldInit) {
					require({
						context: 'aloha'
					}, [
						'aloha'
					], function(Aloha) {
						Aloha.ready(function() {
							if (EditPreviewPanelController.get('currentlyActiveMode.isPreviewMode') !== true) {
								that.enableEdit();
							}
						});
					});
				} else {
					this.enableEdit();
				}

				EntitySelection.initialize();
				this.initializeAlohaEntitySelectionWorkaround();
			},

			enableEdit: function() {
				var editableOptions = this.get('editableOptions'),
					specificEditableOptions;
				$('[about]').each(function() {
					var entity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(this));
					specificEditableOptions = $.extend(true, {model: entity}, editableOptions);
					$(this).midgardEditable(specificEditableOptions);
				});

				this.set('_state', 'edit');
			},

			disableEdit: function() {
				var editableOptions = {
					disabled: true,
					vie: vieInstance
				};
				$('[about]').each(function() {
					$(this).midgardEditable(editableOptions);
					$(this).removeClass('ui-state-disabled');
				});
				this.set('_state', 'browse');
			},

			/**
			 * Refresh an editable by the given DOM element
			 *
			 * @param {Element} element
			 */
			refreshEdit: function(element) {
				var editableOptions = this.get('editableOptions'),
					specificEditableOptions;

				vieInstance.load({
					element: element
				}).from('rdfa').execute().done(function() {
					$(element).find('[about]').add(element).each(function() {
						var entity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(this));
						specificEditableOptions = $.extend(true, {model: entity}, editableOptions);
						$(this).midgardEditable(specificEditableOptions);
					});
				});
			},

			/**
			 * WORKAROUND: When Aloha-Tables inside a content element are selected, we want
			 * to make the full content element selected as well.
			 *
			 * Somehow, Aloha catches bubbling events which we depend upon in the above event
			 * listeners. That's why we also register a listener for "midgardeditableactivated".
			 *
			 * However, *only* depending on this event handler is also not enough, because it is
			 * not thrown for content elements which do not contain any editables.
			 */
			initializeAlohaEntitySelectionWorkaround: function() {
				$(document).on('midgardeditableactivated', '.neos-contentelement', function(e) {
					NodeSelection.updateSelection($(this));
					// make sure that the event is only fired for the *innermost* content element.
					e.stopPropagation();
				});
			}
		});
	}
);
