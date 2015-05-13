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
				var that = this;
				$('[about]').each(function() {
					$(this).midgardEditable(that.get('editableOptions'));
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

			refreshEdit: function($element) {
				$element.midgardEditable(this.get('editableOptions'));
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
