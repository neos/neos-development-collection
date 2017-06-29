define([
	'aloha',
	'jquery',
	'link/link-plugin',
	'ui/ui',
	'i18n!link/nls/i18n',
	'ui/button',
	'ui/toggleButton',
	'ui/port-helper-attribute-field',
	'util/keys'

], function(Aloha, $, LinkPlugin, Ui, i18n, Button, ToggleButton, AttributeField, Keys) {
	// Overwrite default value
	LinkPlugin.hrefValue = '';

	LinkPlugin.createButtons = function () {
		var that = this;

		this._formatLinkButton = Ui.adopt('formatLink', ToggleButton, {
			tooltip: i18n.t('button.addlink.tooltip'),
			icon: 'aloha-icon aloha-icon-link',
			scope: 'Aloha.continuoustext',
			click: function() {
				that.formatLink();
			}
		});

		this._insertLinkButton = Ui.adopt('insertLink', Button, {
			tooltip: i18n.t('button.addlink.tooltip'),
			icon: 'aloha-icon aloha-icon-link',
			scope: 'Aloha.continuoustext',
			click: function() {
				that.insertLink(false);
			}
		});

		this.hrefField = AttributeField({
			name: 'editLink',
			width: 320,
			valueField: 'url',
			cls: 'aloha-link-href-field',
			scope: 'Aloha.continuoustext',
			noTargetHighlight: false,
			targetHighlightClass: 'aloha-focus',
			// Hide link field by default, otherwise it will be shown when linking isn't available
			// Add placeholder
			element: $('<input id="aloha-attribute-field-editLink" placeholder="Paste a link, or search" style="display: none;">')
		});
		this.hrefField.setTemplate('{__thumbnail}<span>{__icon}<b>{name}</b>{__path}</span>'); // This template is customized for Neos
		this.hrefField.setObjectTypeFilter(this.objectTypeFilter);
		this.hrefField.addListener('keyup', function (event) {
			var key = Keys.getToken(event.keyCode);
			if (key === 'enter') {
				var value = that.hrefField.getValue();
				require({context: 'neos'}, ['Shared/Utility'], function(Utility) {
					if (!Utility.isValidLink(value)) {
						var url = 'http://' + value;
						that.hrefField.setAttribute('href', url);
					}
				});
			}
		});

		this._removeLinkButton = Ui.adopt('removeLink', Button, {
			tooltip: i18n.t('button.removelink.tooltip'),
			icon: 'aloha-icon aloha-icon-unlink',
			scope: 'Aloha.continuoustext',
			click: function() {
				that.removeLink();
			}
		});
	};

	return LinkPlugin;
});