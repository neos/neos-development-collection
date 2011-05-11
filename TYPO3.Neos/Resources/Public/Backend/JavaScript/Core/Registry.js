Ext.ns("F3.TYPO3.Core");

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
 * @class F3.TYPO3.Core.Registry
 *
 * The registry provides the structure of all menus used in the application.
 *
 * @namespace F3.TYPO3.Core
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Core.Registry = new (Ext.extend(Ext.util.Observable, {

	/**
	 * Intermediate or final configuration (built after calling compile)
	 *
	 * @private
	 */
	_configuration: null,

	/**
	 * Initialize the registry
	 */
	initialize: function() {
		this._configuration = {
			_children: {}
		};
	},

	/**
	 * Compiles the registry configuration after all operations have been added
	 */
	compile: function() {
		this._configuration = this._compileVisit(this._configuration);
	},

	/**
	 * Recursive compilation of registry
	 *
	 * @param {Object} context The current object in the registry configuration
	 * @return {Object} The object with compiled children and all operations applied
	 * @private
	 */
	_compileVisit: function(context) {
		var child;
		if (!F3.TYPO3.Utils.isEmptyObject(context._children)) {
			for (child in context._children) {
				context[child] = this._compileVisit(context._children[child]);
			}
			delete context._children;
		}
		return this._apply_operations(context);
	},

	/**
	 * Apply operations to a configuration object after all
	 * children were compiled.
	 *
	 * @param {Object} context The current object in the registry configuration
	 * @return {Object} The object with all operations applied
	 * @private
	 */
	_apply_operations: function(context) {
		var result;
		if (context._operations !== undefined) {
			if (context._operations.remove !== undefined) {
				return undefined;
			} else if (context._operations.set !== undefined) {
				context._operations.set.sort(function(op1, op2) {
					return (op2.priority - op1.priority);
				});
				return context._operations.set[0].value;
			} else if (context._operations.append !== undefined ||
				context._operations.prepend !== undefined ||
				context._operations.insertAfter !== undefined ||
				context._operations.insertBefore !== undefined) {
				result = [];
				if (context._operations.prepend !== undefined) {
					context._operations.prepend.sort(function(op1, op2) {
						return (op2.priority - op1.priority);
					});

					Ext.each(context._operations.prepend, function(operation) {
						if (context[operation.key] !== undefined) {
							context[operation.key]['key'] = operation.key;
							result.push(context[operation.key]);
						}
					});
				}
				if (context._operations.append !== undefined) {
					context._operations.append.sort(function(op1, op2) {
						return (op1.priority - op2.priority);
					});

					Ext.each(context._operations.append, function(operation) {
						if (context[operation.key] !== undefined) {
							context[operation.key]['key'] = operation.key;
							result.push(context[operation.key]);
						}
					});
				}

					// Process insertAfter and insertBefore operations
				result = this._processInsertRelativeToPosition(context, 'insertAfter', result, 1);
				result = this._processInsertRelativeToPosition(context, 'insertBefore', result, -1);

				return result;
			}
		}

		delete context._operations;
		return context;
	},

	/**
	 * Process a list of operations and apply them to an object. Adds the operations on a position relative
	 * to another operation (before or after).
	 *
	 * @param {Object} context The current object in the registry configuration
	 * @param {String} operationContextKey The property of context in which the operation array is stored
	 * @param {Object} result The source object to which all operations will be applied
	 * @param {Integer} position The position to add the operations. After = 1, Before = -1
	 * @return {Object} The object with all operations applied
	 * @private
	 */
	_processInsertRelativeToPosition: function(context, operationContextKey, result, position) {
		if (context._operations[operationContextKey] !== undefined) {
			context._operations[operationContextKey].sort(function(op1, op2) {
				if (position > 0) {
					return (op2.priority - op1.priority);
				} else {
					return (op1.priority - op2.priority);
				}
			});

			while (context._operations[operationContextKey].length > 0) {
				var operationIterationIndex = 0,
					insertableOperationFound = false;
				Ext.each(context._operations[operationContextKey], function(operation) {
					var index = F3.TYPO3.Utils.findIndexOf(result, function(entry) {
						return entry.key === operation.position;
					});

					if (index > -1) {
						insertableOperationFound = true;
						context[operation.key]['key'] = operation.key;

						var newIndex = index + (position > 0 ? 1 : 0);
						console.log(operationContextKey, newIndex);
						result = result.slice(0, newIndex).concat([context[operation.key]]).concat(result.slice(newIndex));

						context._operations[operationContextKey].splice(operationIterationIndex, 1);

							// Break iteration
						return false;
					}
					operationIterationIndex ++;
				});

				if (insertableOperationFound === false) {
					break;
				}
			}

				// Add pending operations to the end
			Ext.each(context._operations[operationContextKey], function(operation) {
				context[operation.key]['key'] = operation.key;
				result.push(context[operation.key]);
			});

		}
		return result;
	},

	/**
	 * Set a value at "path" with the given priority.
	 *
	 * @param {String} path
	 * @param {Object} value
	 * @param {Integer} priority
	 */
	set: function(path, value, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this._configuration, path),
			key;
		if (priority === undefined) {
			priority = 0;
		}

		if (Ext.isObject(value)) {
			for (key in value) {
				this.set(path + '/' + key, value[key], priority);
			}
		} else {
			if (context._operations.set === undefined) {
				context._operations.set = [];
			}
			context._operations.set.push({
				value: value,
				priority: priority
			});
		}
	},

	/**
	 * Append a value to the array at "path"
	 *
	 * @param {String} path The path pointing to an array
	 * @param {String} key The array key of the new element
	 * @param {Object} value The value to be appended
	 * @param {Integer} priority Priority of the operation
	 */
	append: function(path, key, value, priority) {
		this._addOperation('append', path, key, value, priority);
		this.set(path + '/' + key, value, priority);
	},

	/**
	 * Prepend a value to the array at "path"
	 *
	 * @param {String} path The path pointing to an array
	 * @param {String} key The array key of the new element
	 * @param {Object} value The value to be prepended
	 * @param {Integer} priority Priority of the operation
	 */
	prepend: function(path, key, value, priority) {
		this._addOperation('prepend', path, key, value, priority);
		this.set(path + '/' + key, value, priority);
	},

	/**
	 * Add an append or prepend operation
	 *
	 * @param {String} path The path pointing to an array
	 * @param {String} key The array key of the new element
	 * @param {Object} value The value to be appended / prepended
	 * @param {Integer} priority Priority of the operation
	 * @private
	 */
	_addOperation: function(operation, path, key, value, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this._configuration, path),
			key;
		if (priority === undefined) {
			priority = 0;
		}
		if (context._operations[operation] === undefined) {
			context._operations[operation] = [];
		}
		context._operations[operation].push({
			key: key,
			priority: priority
		});
	},

	/**
	 * Remove the element at "path"
	 *
	 * @param {String} path The path to be removed
	 * @param {Integer} priority Priority of the operation
	 */
	remove: function(path, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this._configuration, path);
		if (priority === undefined) {
			priority = 0;
		}
		if (context._operations.remove === undefined) {
			context._operations.remove = [];
		}
		context._operations.remove.push({
			priority: priority
		});
	},

	/**
	 * Insert the new element into an array, after the element which
	 * is referenced by "path".
	 *
	 * @param {String} path The path pointing to an array element
	 * @param {String} key The array key of the new element
	 * @param {Object} value The value to be inserted
	 * @param {Integer} priority Priority of the operation
	 */
	insertAfter: function(pathWithPosition, key, value, priority) {
		pathWithPosition = this.rewritePath(pathWithPosition);
		var pathParts = pathWithPosition.split('/'),
			arrayPath = pathParts.slice(0, -1).join('/'),
			position = pathParts[pathParts.length - 1],
			context = this._getOrCreatePath(this._configuration, arrayPath),
			key;
		if (priority === undefined) {
			priority = 0;
		}
		if (context._operations.insertAfter === undefined) {
			context._operations.insertAfter = [];
		}
		context._operations.insertAfter.push({
			position: position,
			key: key,
			priority: priority
		});
		this.set(arrayPath + '/' + key, value, priority);
	},

	/**
	 * Insert the new element into an array, before the element which
	 * is referenced by "path".
	 *
	 * @param {String} path The path pointing to an array element
	 * @param {String} key The array key of the new element
	 * @param {Object} value The value to be inserted
	 * @param {Integer} priority Priority of the operation
	 */
	insertBefore: function(pathWithPosition, key, value, priority) {
		pathWithPosition = this.rewritePath(pathWithPosition);
		var pathParts = pathWithPosition.split('/'),
			arrayPath = pathParts.slice(0, -1).join('/'),
			position = pathParts[pathParts.length - 1],
			context = this._getOrCreatePath(this._configuration, arrayPath),
			key;
		if (priority === undefined) {
			priority = 0;
		}
		if (context._operations.insertBefore === undefined) {
			context._operations.insertBefore = [];
		}
		context._operations.insertBefore.push({
			position: position,
			key: key,
			priority: priority
		});
		this.set(arrayPath + '/' + key, value, priority);
	},

	/**
	 * Get the data inside the registry at the given path.
	 *
	 * @param {String} path The path to fetch in the registry path syntax
	 * @return {Object} The data inside the registry at the given path
	 */
	get: function(path) {
		if (path === undefined) {
			path = '';
		} else {
			path = this.rewritePath(path);
		}

		var resultFound = true,
			parts = path.split('/'),
			context = this._configuration;

		if (path === '') return context;

		Ext.each(parts, function(part) {
			if (context.constructor == (new Array).constructor) {
				Ext.each(context, function(contextElement) {
					if (contextElement.key == part) {
						context = contextElement;
						return false;
					}
				});
			} else if (context[part] === undefined) {
				resultFound = false;
			} else {
				context = context[part];
			}
		});

		if (!resultFound) {
			return null;
		}
		return F3.TYPO3.Utils.clone(context);
	},

	/**
	 * Expand a path with brackets in there (like 'menu/main[]')
	 * to its expanded form ('menu/main/children').
	 *
	 * @param {String} path The path to rewrite
	 * @return {String} The expanded path
	 */
	rewritePath: function(path) {
		path = path.replace(/\[\]/g, '[children]');
		path = path.replace(/\[([^\]]+)\]/g, '/$1');
		return path;
	},

	/**
	 * Create or get a path inside the object structure
	 *
	 * @param {Object} The context (e.g. configuration)
	 * @param {String} The path in expanded form to get or create
	 * @return {Object} The object at the given path
	 * @private
	 */
	_getOrCreatePath: function(object, path) {
		var parts = path.split('/'),
			context = object;
		Ext.each(parts, function(part) {
			if (context._children[part] === undefined) {
				context._children[part] = {
					_operations: {},
					_children: {}
				};
			}
			context = context._children[part];
		});
		return context;
	}
}));