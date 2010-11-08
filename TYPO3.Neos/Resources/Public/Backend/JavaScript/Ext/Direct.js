/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

// TODO: Document this class thoroughly!
Ext.form.Action.F3DirectSubmit = Ext.extend(Ext.form.Action.DirectSubmit, {
	run: function() {
		var o = this.options,
			values;
		if (o.clientValidation === false || this.form.isValid()) {
			this.success.params = this.getParams();
			values = Ext.apply(this.form.getValues(), o.additionalValues);
			this.form.api.submit(values, this.success, this);
		} else if (o.clientValidation !== false) {
			this.failureType = Ext.form.Action.CLIENT_INVALID;
			this.form.afterAction(this, false);
		}
	}
});
Ext.form.Action.ACTION_TYPES['directsubmit'] = Ext.form.Action.F3DirectSubmit;

Ext.Direct.on('exception', function(event) {
	if (window.console && console.error) {
		console.error(event.message, event.where);
	}
});
