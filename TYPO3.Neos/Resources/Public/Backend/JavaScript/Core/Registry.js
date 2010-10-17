Ext.ns("F3.TYPO3.Core");
/**
 * @class F3.TYPO3.Core.Registry
 * @namespace F3.TYPO3.Core
 * @extends Ext.util.Observable
 *
 * The registry provides the structure of all menus used in the application.
 *
 * @singleton
 */
F3.TYPO3.Core.Registry = new (Ext.extend(Ext.util.Observable, {

	/**
	 * Intermediate or final configuration (built after calling compile)
	 *
	 * @private
	 */
	configuration: null,

	/**
	 * Initialize the registry
	 */
	initialize: function() {
		this.configuration = {
			_children: {}
		};
	},

	/**
	 * Compiles the registry configuration after all operations have been added
	 */
	compile: function() {
		this.configuration = this._compileVisit(this.configuration);
	},

	/**
	 * Recursive compilation of registry
	 *
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

	_apply_operations: function(context) {
		var result,
			index,
			findIndexOf = function(array, filter) {
				var i;
				for (i = 0; i < array.length; i++) {
					if (filter(array[i])) {
						return i;
					}
				}
				return -1;
			};
		if (context._operations !== undefined) {
			if (context._operations.remove !== undefined) {
				return undefined;
			} else if (context._operations.set !== undefined) {
				context._operations.set.sort(function(op1, op2) {
					return op1.priority < op2.priority;
				});
				return context._operations.set[0].value;
			} else if (context._operations.append !== undefined ||
				context._operations.prepend !== undefined ||
				context._operations.insertAfter !== undefined ||
				context._operations.insertBefore !== undefined) {
				result = [];
				if (context._operations.prepend !== undefined) {
					context._operations.prepend.sort(function(op1, op2) {
						return op1.priority < op2.priority;
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
						return op1.priority > op2.priority;
					});

					Ext.each(context._operations.append, function(operation) {
						if (context[operation.key] !== undefined) {
							context[operation.key]['key'] = operation.key;
							result.push(context[operation.key]);
						}
					});
				}
				if (context._operations.insertBefore !== undefined) {
					context._operations.insertBefore.sort(function(op1, op2) {
						return op1.priority > op2.priority;
					});

					// TODO We should iterate more than once!
					Ext.each(context._operations.insertBefore, function(operation) {
						index = findIndexOf(result, function(entry) {
							return entry.key === operation.position;
						});
						context[operation.key]['key'] = operation.key;
						if (index >= 0) {
							result = result.slice(0, index).concat([context[operation.key]]).concat(result.slice(index));
						} else {
							// Add pending _operations to the end
							result.push(context[operation.key]);
						}
					});
				}
				if (context._operations.insertAfter !== undefined) {
					context._operations.insertAfter.sort(function(op1, op2) {
						return op1.priority < op2.priority;
					});

					// TODO We should iterate more than once!

					Ext.each(context._operations.insertAfter, function(operation) {
						index = findIndexOf(result, function(entry) {
							return entry.key === operation.position;
						});
						context[operation.key]['key'] = operation.key;
						if (index >= 0) {
							result = result.slice(0, index + 1).concat([context[operation.key]]).concat(result.slice(index + 1));
						} else {
							// Add pending _operations to the end
							result.push(context[operation.key]);
						}
					});
				}
				return result;
			}
		}

		delete context._operations;
		return context;
	},

	set: function(path, value, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this.configuration, path),
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
	append: function(path, key, value, priority) {
		this._addOperation('append', path, key, value, priority);
		this.set(path + '/' + key, value, priority);
	},

	prepend: function(path, key, value, priority) {
		this._addOperation('prepend', path, key, value, priority);
		this.set(path + '/' + key, value, priority);
	},

	_addOperation: function(operation, path, key, value, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this.configuration, path),
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

	remove: function(path, priority) {
		path = this.rewritePath(path);
		var context = this._getOrCreatePath(this.configuration, path);
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

	insertAfter: function(pathWithPosition, key, value, priority) {
		pathWithPosition = this.rewritePath(pathWithPosition);
		var pathParts = pathWithPosition.split('/'),
			arrayPath = pathParts.slice(0, -1).join('/'),
			position = pathParts[pathParts.length - 1],
			context = this._getOrCreatePath(this.configuration, arrayPath),
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

	insertBefore: function(pathWithPosition, key, value, priority) {
		pathWithPosition = this.rewritePath(pathWithPosition);
		var pathParts = pathWithPosition.split('/'),
			arrayPath = pathParts.slice(0, -1).join('/'),
			position = pathParts[pathParts.length - 1],
			context = this._getOrCreatePath(this.configuration, arrayPath),
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

	get: function(path) {
		if (path === undefined) {
			path = '';
		} else {
			path = this.rewritePath(path);
		}

		var parts = path.split('/'),
			context = this.configuration;
		Ext.each(parts, function(part) {
			if (context[part] === undefined) {
				return undefined;
			}
			context = context[part];
		});
		return context;
	},

	rewritePath: function(path) {
		path = path.replace(/\[\]/g, '[children]');
		path = path.replace(/\[([^\]]+)\]/g, '/$1');
		return path;
	},

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