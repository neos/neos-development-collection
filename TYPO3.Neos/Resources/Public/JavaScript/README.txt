Examples for Registry Entries
=============================

PRIORITY: highest priority of applied operation (highest wins)

SET: sets a value, replaces everything in there, i.e. highest priority wins (COMPLETELY)
	- set configuration
	- convert the type for a certain text field from "input" to "textarea"

APPEND: - append an element to an END of the array

PREPEND: - prepend an element to the BEGINNING of an array

INSERT: at specific point

KEY: Named array entry

insertAfter

insertBefore (... key ...)

=====================

set('foo', 'bar');

{
	foo: {
		operations: {
			set: [{
				value: 'bar',
				priority: 0
			}]
		},
		children: {}
	}
}

{ foo: "bar" }

================

set('foo', ['a', 'b']);

{
	foo: {
		operations: {
			set: [{
				value: ['a', 'b'],
				priority: 0
			}]
		},
		children: {}
	}
}

{ foo: ['a', 'b'] }

================

set('foo', ['x', 'y'], 100); // Higher priority
set('foo', ['a', 'b']);
{
	foo: {
		operations: {
			set: [{
				value: ['x', 'y'],
				priority: 100
			}, {
				value: ['a', 'b'],
				priority: 0
			}
		},
		children: {}
	}
}

{ foo: ['x', 'y'] }

======================
REWRITING:

set('foo/bar', 'baz');
==
set('foo', {bar: 'baz'}) -> set('foo/bar', 'baz', 0)

{
	foo: {
		operations: {},
		children: {
			bar: {
				operations: {
					set: [{
						value: 'baz',
						priority: 0
					}]
				}
			}
		}
	}
}

{foo: {bar: ' baz'}}

=================
TWO PROPERTIES overriding nested properties
set('foo', {bar: 'baz', x: 'y'}) -> set('foo/bar', 'baz', 0) && set('foo/x', 'y', 0)
set('foo/bar', 'HELLO', 10);

{
	foo: {
		operations: {},
		children: {
			bar: {
				operations: {
					set: [{
						value: 'baz',
						priority: 0
					},{
						value: 'HELLO',
						priority: 10
					}]
				}
			},
			x: {
				operations: {
					set: [{
						value: 'y',
						priority: 0
					}]
				}
			}
		}
	}
}

{foo: {bar: 'HELLO', x: 'y'}}

=================
Arrays:
append("menu/main", 'edit', { // Path, Key, Value
	title: 'Edit'
});
=> set("menu/main/edit/title", "Edit")

{
	menu: {
		operations: {},
		children: {
			main: {
				operations: {
					append: [{
						key: 'edit',
						priority: 0
					}]
				},
				children: {
					edit: {
						children:{
							title: {
								operations: {
									set: [{
										value: 'Edit',
										priority: 0
									}]
								}
							}
						}
					}
				}
			}
		}
	}
}

SET with array => multiple appends


{
	menu: {
		main: [{
			title: 'Edit',
		}]
	}
}




- FIRST evaluate children, THEN evaluate operations
- ORDER of operations
	- set -> for primitive types, or converted to multiple SET operations
	- delete
	- prepend (Arrays) => append with negative priorities
	- append (Arrays)
		-> should not fail if element in "children" is not there anymore, as it could have been deleted
	- insertAfter / insertBefore --> warning if element could not be inserted
- Errors in the following cases:
	- set & append in operations list

PRIORITIES: NO negative numbers


Datastructure after compilation:
arbitary JSON


================================================================================


{
	schema: {
		"typo3:page": {
			service: {
				load: F3.TYPO3...NodeService.load,
				store: F3.TYPO3...NodeService.store
			},
			properties: {
				// Flach, da assoc. array
				'properties.title': {
					type: 'string',
					validations: [{
						key: 'v1',
						type: 'NotEmpty'
					}, {
						key: 'v2',
						type: 'Label'
					}, {
						key: 'v3',
						type: 'StringLength',
						options: {
							maximum: 50
						}
					}]
				},
				'properties.navigationTitle': {
					type: 'string'
				}
			}
		},
		'F3...Person': {
			properties: {
				personName: {
					type: 'F3...PersonName'
				}
			}
		},
		'F3...PersonName': {
			...
		}
	},
	form: {
		editor: {
			// By type
			"string": {
				xtype: 'textfield'
			},
			"superStringEditor": {
				xtype: 'textarea',
				transform: ...
			}
		}
		type: {
			"typo3:page": {
				default: {
					title: 'Page',
					children: [{
						key: 'pageProperties',
						type: 'fieldset',
						title: 'Page properties',
						children: [{
							key: 'title',
							type: 'field',
							property: 'properties.title',
							label: 'Page title'
						}, {
							key: 'navigationTitle',
							type: 'field',
							property: 'properties.navigationTitle',
							label: 'Navigation title'
						}]
					}]
				},
				pageProperties: {
					title: 'Page properties',
					children: [{
						key: 'title',
						type: 'field',
						property: 'properties.title',
						label: 'Page title'
					}, {
						key: 'navigationTitle',
						type: 'field',
						property: 'properties.navigationTitle',
						label: 'Navigation title'
					}]
				}
			}
		}
	}
}

---->

{
	xtype: 'F3.TYPO3.Components.Form.GenericForm',
	type: 'typo3:page',
	view: 'pageProperties'
}

