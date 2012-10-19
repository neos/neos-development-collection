define(
	['jquery', 'emberjs', 'text!phoenix/templates/halloplugins/linkplugin.html', 'jquery-ui'],
	function($, Ember, linkPluginTemplate) {
		(function($) {
			return $.widget('typo3.hallo-linkplugin', {
				view: null,

				options: {
					editable: null,
					uuid: '',
					link: true,
					defaultUrl: 'http://',
					dialogOpts: {
						autoOpen: false,
						width: 540,
						height: 95,
						title: 'Enter Link',
						modal: true,
						resizable: false,
						draggable: false,
						dialogClass: 'hallolink-dialog aloha-block-do-not-deactivate'
					},
					butonCssClass: null
				},

				populateToolbar: function(toolbar) {
					var buttonize, buttonset, dialog, dialogId, widget,
						_this = this;
					widget = this;
					dialogId = '' + this.options.uuid + '-dialog';
					dialog = $('<div />', {id: dialogId});

					widget.view = Ember.View.create({
						classNames: ['t3-ui'],
						template: Ember.Handlebars.compile(linkPluginTemplate),

						url: widget.options.defaultUrl,
						label: 'Insert',

						didInsertElement: function() {
							var that = this;
							this.$('.t3-link-inputfield').focus();
							this.$('.t3-link-inputfield').autocomplete({
								source: function(request, response) {
									TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.searchPage(request.term, function(result) {
										if (result.searchResult) {
											response($.map(result.searchResult, function(node) {
												return {
													label: node.name,
													value: node.url
												}
											}));
										} else {
											response([]);
										}
									});
								},
								minLength: 2,
								select: function(event, ui) {
									that.set('url', ui.item.value);
								}
							},
							this.$('.t3-link-inputfield').keyup(function(e) {
								if (e.keyCode === 13) {
									that.insert();
								}
							})
							);

							if (this.get('url') !== widget.options.defaultUrl) {
								this.set('label', 'Update');
							}
						},

						insert: function() {
							var link = this.get('url');

							widget.options.editable.restoreSelection(widget.lastSelection);

							if (((new RegExp(/^\s*$/)).test(link)) || link === widget.options.defaultUrl) {
								if (widget.lastSelection.collapsed) {
									widget.lastSelection.setStartBefore(widget.lastSelection.startContainer);
									widget.lastSelection.setEndAfter(widget.lastSelection.startContainer);
									window.getSelection().addRange(widget.lastSelection);
								}
								document.execCommand('unlink', null, '');
							} else {
								if (widget.lastSelection.startContainer.parentNode.href === void 0) {
									document.execCommand('createLink', null, link);
								} else {
									widget.lastSelection.startContainer.parentNode.href = link;
								}
							}
							widget.options.editable.element.trigger('change');
							widget.options.editable.removeAllSelections();
							dialog.dialog('close');
							return false;
						},
						cancel: function() {
							dialog.dialog('close');
							return false;
						}
					}).appendTo(dialog);

					buttonset = $('<span class="' + widget.widgetName + '"></span>');
					buttonize = function(type) {
						var button, buttonHolder;

						buttonHolder = $('<span />');
						buttonHolder.hallobutton({
							label: 'Link',
							icon: 'icon-link',
							editable: _this.options.editable,
							command: null,
							queryState: false,
							uuid: _this.options.uuid,
							cssClass: _this.options.buttonCssClass
						});
						buttonset.append(buttonHolder);
						button = buttonHolder;
						button.bind('click', function() {
							widget.lastSelection = widget.options.editable.getSelection();

							widget.options.editable.keepActivated(true);
							dialog.dialog('open');
							dialog.bind('dialogclose', function() {
								$('label', buttonHolder).removeClass('ui-state-active');
								widget.options.editable.element.focus();
								return widget.options.editable.keepActivated(false);
							});

							if (widget.lastSelection.startContainer.parentNode.href) {
								widget.view.set('url', $(widget.lastSelection.startContainer.parentNode).attr('href'));
								widget.view.set('label', 'Update');
							} else {
								widget.view.set('url', widget.options.defaultUrl);
								widget.view.set('label', 'Insert');
							}

							return false;
						});
						return _this.element.bind('keyup paste change mouseup', function(event) {
							var nodeName, start;
							start = $(widget.options.editable.getSelection().startContainer);
							nodeName = start.prop('nodeName') ? start.prop('nodeName') : start.parent().prop('nodeName');
							if (nodeName && nodeName.toUpperCase() === 'A') {
								$('label', button).addClass('ui-state-active');
								return;
							}
							return $('label', button).removeClass('ui-state-active');
						});
					};
					if (this.options.link) {
						buttonize('A');

						toolbar.append(buttonset);
						buttonset.hallobuttonset();
						return dialog.dialog(this.options.dialogOpts);
					}
				},
				_init: function() {}
			});
		})($);
	}
);