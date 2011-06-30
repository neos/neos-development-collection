Ext.ns('TYPO3.TYPO3.Utility.Queue');

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

/**
 * @class TYPO3.TYPO3.Utility.Queue.TimeBasedQueue
 *
 * Time based queue. Stores a queue of actions, and triggers them based
 * on an interval set on the registration of the action in the queue
 *
 * @namespace TYPO3.TYPO3.Utility.Queue
 */

TYPO3.TYPO3.Utility.Queue.TimeBasedQueue = Ext.extend(function() {}, {

	/**
	 * Array with registered actions, queued to be executed
	 *
	 * @private
	 */
	_queue: [],

	/**
	 * Running status of the queue
	 *
	 * @private
	 */
	_running: false,

	/**
	 * Stop execution of the queue and remove all actions from the queue
	 *
	 * @return {Void}
	 * @api
	 */
	reset: function() {
		this._queue = [];
		this._running = false;
	},

	/**
	 * Register a new action in the queue.
	 *
	 * @param {Function} action function to execute
	 * @param {Integer} sleepTime time to wait after execution of the action
	 * @return {Void}
	 * @api
	 */
	add: function(action, sleepTime) {
		this._queue.push({
			action: action,
			sleepTime: sleepTime
		});
	},

	/**
	 * Start the execution of elements in the queue
	 *
	 * @return {Void}
	 * @api
	 */
	start: function() {
		if (this._running == false) {
			this._running = true;
			this._executeQueueCycle();
		}
	},

	/**
	 * Stop the execution of the queue
	 *
	 * @return {Void}
	 * @api
	 */
	stop: function() {
		this._running = false;
	},

	/**
	 * Count the actions still waiting to be executed
	 *
	 * @return {Integer}
	 * @api
	 */
	count: function() {
		return this._queue.length;
	},

	/**
	 * Is the queue running
	 *
	 * @return {Boolean}
	 * @api
	 */
	isRunning: function() {
		return this._running;
	},

	/**
	 * Execute a single entry in the queue
	 *
	 * @private
	 * @return {Void}
	 */
	_executeQueueCycle: function() {
		if (this._queue.length > 0) {
			var cycle = this._queue.shift();
			cycle.action.call();
		}

		if (this._queue.length > 0) {
			if (cycle.sleepTime == 0) {
				this._executeQueueCycle();
			} else {
				var scope = this;
				setTimeout(function() {
					scope._executeQueueCycle();
				}, cycle.sleepTime);
			}
			return null;
		}

		this._running = false;
	}

});