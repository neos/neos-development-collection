/**
 * Notification handler
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Library/handlebars',
	'Library/toastr',
	'text!./Notification.html'
],
function(
	Ember,
	$,
	handlebars,
	toastr,
	template
) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		template: handlebars.compile(template),

		/**
		 * @return {void}
		 */
		init: function() {
			toastr.options = {
				tapToDismiss: false,
				toastClass: 'neos-notification',
				containerId: 'neos-notification-container',
				iconClasses: {
					error: 'neos-notification-error',
					info: 'neos-notification-info',
					success: 'neos-notification-success',
					warning: 'neos-notification-warning'
				},
				titleClass: 'neos-title',
				messageClass: 'neos-message',
				closeHtml: '<i class="fas fa-times"></i>',
				closeButton: false,
				positionClass: 'neos-notification-top',
				showMethod: 'slideDown',
				hideMethod: 'slideUp',
				hideDuration: 500,
				showEasing: 'easeOutBounce',
				hideEasing: 'easeInCubic',
				timeOut: 5000,
				target: '#neos-application'
			};
			var notifications = $('#neos-notifications-inline');
			if (notifications.length > 0) {
				var that = this;
				setTimeout(function() {
					$('li', notifications).each(function(index, notification) {
						var title = $(notification).data('title');
						that[$(notification).data('type')](title ? title : $(notification).text(), title ? $(notification).html() : '');
					});
				}, 250);
			}
		},

		/**
		 * Render template
		 *
		 * @param {string} type
		 * @param {string} title
		 * @param {string} message
		 * @return {string}
		 */
		_render: function(type, title, message) {
			var template = this.get('template');
			return template({type: type, title: title, message: message ? message.htmlSafe() : ''});
		},

		/**
		 * Show ok notification
		 *
		 * @param {string} title
		 * @return {void}
		 */
		ok: function(title) {
			toastr.success(this._render('success', title, ''), title);
		},

		/**
		 * Show info notification
		 *
		 * @param {string} title
		 * @return {void}
		 */
		info: function(title) {
			toastr.info(this._render('info', title, ''), title);
		},

		/**
		 * Show notice notification
		 *
		 * @param {string} title
		 * @return {void}
		 */
		notice: function(title) {
			this.info(title);
		},

		/**
		 * Show warning notification
		 *
		 * @param {string} title
		 * @param {string} message
		 * @return {void}
		 */
		warning: function(title, message) {
			toastr.warning(this._render('warning', title, message), title, {timeOut: 0, extendedTimeOut: 0, closeButton: true});
			this._registerExpandHandler();
		},

		/**
		 * Show error notification
		 *
		 * @param {string} title
		 * @param {string} message
		 * @return {void}
		 */
		error: function(title, message) {
			toastr.error(this._render('error', title, message), title, {timeOut: 0, extendedTimeOut: 0, closeButton: true});
			this._registerExpandHandler();
		},

		/**
		 * Clears all notifications
		 *
		 * @return {void}
		 */
		clear: function() {
			toastr.clear();
		},

		_registerExpandHandler: function() {
			$('.neos-notification-content.expandable .neos-notification-heading', '#neos-notification-container').unbind('click').click(function(){
				$(this).parent().toggleClass('expanded');
				$(this).next('.neos-expand-content').slideToggle();
			});
		}
	}).create();
});