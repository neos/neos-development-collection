Ext.ns('TYPO3.TYPO3.Core');

describe("Test Registry", function() {

	var registry;

	beforeEach(function() {
		registry = TYPO3.TYPO3.Core.Registry;
		registry.initialize();
	});

	it("Set simple value and default priority.", function() {
		registry.set('foo', 'bar');

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				foo: {
					_operations: {
						set: [{
							value: 'bar',
							priority: 0
						}]
					},
					_children: {}
				}
			}
		}));
	});

	it("Set with priority.", function() {
		registry.set('foo', 'bar', 50);

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				foo: {
					_operations: {
						set: [{
							value: 'bar',
							priority: 50
						}]
					},
					_children: {}
				}
			}
		}));
	});

	it("Set with path.", function() {
		registry.set('foo/bar', 'baz');

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				foo: {
					_operations: {},
					_children: {
						bar: {
							_operations: {
								set: [{
									value: 'baz',
									priority: 0
								}]
							},
							_children: {}
						}
					}
				}
			}
		}));
	});


	it("Set rewrites object value to set with path.", function() {
		registry.set('foo', {bar: 'baz', x: 'y'});

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				foo: {
					_operations: {},
					_children: {
						bar: {
							_operations: {
								set: [{
									value: 'baz',
									priority: 0
								}]
							},
							_children: {}
						},
						x: {
							_operations: {
								set: [{
									value: 'y',
									priority: 0
								}]
							},
							_children: {}
						}
					}
				}
			}
		}));
	});

	it("Append calls set and registers append", function() {
		registry.append('menu/main', 'edit', {title: 'Edit'});

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				menu: {
					_operations: {},
					_children: {
						main: {
							_operations: {
								append: [{
									key: 'edit',
									priority: 0
								}]
							},
							_children: {
								edit: {
									_operations: {},
									_children: {
										title: {
											_operations: {
												set: [{
													value: 'Edit',
													priority: 0
												}]
											},
											_children: {}
										}
									}
								}
							}
						}
					}
				}
			}
		}));
	});

	it("Prepend calls set and registers prepend.", function() {
		registry.prepend('menu/main', 'edit', {title: 'Edit'}, 42);

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				menu: {
					_operations: {},
					_children: {
						main: {
							_operations: {
								prepend: [{
									key: 'edit',
									priority: 42
								}]
							},
							_children: {
								edit: {
									_operations: {},
									_children: {
										title: {
											_operations: {
												set: [{
													value: 'Edit',
													priority: 42
												}]
											},
											_children: {}
										}
									}
								}
							}
						}
					}
				}
			}
		}));
	});

	it("Insert after adds insert operation", function() {
		registry.insertAfter('menu/main/edit', 'preview', {title: 'Preview'});

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				menu: {
					_operations: {},
					_children: {
						main: {
							_operations: {
								insertAfter: [{
									position: 'edit',
									key: 'preview',
									priority: 0
								}]
							},
							_children: {
								preview: {
									_operations: {},
									_children: {
										title: {
											_operations: {
												set: [{
													value: 'Preview',
													priority: 0
												}]
											},
											_children: {}
										}
									}
								}
							}
						}
					}
				}
			}
		}));
	});

	it("Insert before adds insert operation.", function() {
		registry.insertBefore('menu/main/edit', 'preview', {title: 'Preview'}, 50);

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				menu: {
					_operations: {},
					_children: {
						main: {
							_operations: {
								insertBefore: [{
									position: 'edit',
									key: 'preview',
									priority: 50
								}]
							},
							_children: {
								preview: {
									_operations: {},
									_children: {
										title: {
											_operations: {
												set: [{
													value: 'Preview',
													priority: 50
												}]
											},
											_children: {}
										}
									}
								}
							}
						}
					}
				}
			}
		}));
	});

	it ("Remove adds remove operation.", function() {
		registry.remove('menu/main/preview', 60);

		expect(Ext.encode(registry._configuration)).toEqual(Ext.encode({
			_children: {
				menu: {
					_operations: {},
					_children: {
						main: {
							_operations: {},
							_children: {
								preview: {
									_operations: {
										remove: [{
											priority: 60
										}]
									},
									_children: {}
								}
							}
						}
					}
				}
			}
		}));
	});

	it ("Compile converts set with priority.", function() {
		registry.set('foo', 'bar');
		registry.set('foo', 'baz', 10);

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			foo: 'baz'
		}));
	});

	it ("Compile converts nested set.", function() {
		registry.set('foo/bar', 'x', 50);
		registry.set('foo', {bar: 'baz', x: 'y'});

		registry.compile();

		expect(registry.get()).toEqual({
			foo: {
				bar: 'x',
				x: 'y'
			}
		});
	});

	it ("Compile converts nested set with numeric keys.", function() {
		/**
		 * Compared to previous test:
		 * 	0 = foo
		 * 	1 = bar
		 * 	2 = x
		 * 	3 = baz
		 * 	4 = y
		 */
		registry.set('0/1', '2', 50);
		registry.set('0', {1: '3', 2: '4'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			0: {
				1: '2',
				2: '4'
			}
		}));
	});

	it ("Compile with override.", function() {
		registry.set('foo/bar', 'x');
		registry.set('foo', 'y', 50);

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			foo: 'y'
		}));
	});

	it ("Compile removes value.", function() {
		registry.remove('foo/bar');
		registry.set('foo/bar', 'baz');

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			foo: {
				bar: undefined
			}
		}));
	});

	it ("Compile with multiple append.", function() {
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		registry.append('menu/main', 'edit', {title: 'Edit'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
					title: 'Delete',
					key: 'delete'
				}]
			}
		}));
	});

	it ("Get with path.", function() {
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		registry.append('menu/main', 'edit', {title: 'Edit'});

		registry.compile();

		expect(Ext.encode(registry.get('menu/main'))).toEqual(Ext.encode(
			[{
				title: 'Edit',
				key: 'edit'
			}, {
				title: 'Delete',
				key: 'delete'
			}]
		));
	});

	it ("Compile with multiple append set and remove.", function() {
		registry.remove('menu/main/delete');
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		registry.set('menu/main/edit', {title: 'Bearbeiten'}, 10);
		registry.append('menu/main', 'edit', {title: 'Edit'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Bearbeiten',
					key: 'edit'
				}]
			}
		}));
	});

	it ("Compile with multi level append.", function() {
		registry.append('menu/main', 'content', {title:'Content'});
		registry.append('menu/main/content[]', 'pages', {title:'Pages'});
		registry.append('menu/main/content[]/pages[]', 'new', {title:'New'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Content',
					children: [{
						title: 'Pages',
						children: [{
							title: 'New',
							key: 'new'
						}],
						key: 'pages'
					}],
					key: 'content'
				}]
			}
		}));
	});

	it ("Compile with prepend and append.", function() {
		registry.append('menu/main', 'delete', {title: 'Delete'});
		registry.prepend('menu/main', 'edit', {title: 'Edit'}, 10);
		registry.prepend('menu/main', 'preview', {title: 'Preview'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
					title: 'Preview',
					key: 'preview'
				}, {
					title: 'Delete',
					key: 'delete'
				}]
			}
		}));
	});

	it ("Compile with insert after appends missing keys.", function() {
		registry.insertAfter('menu/main/preview', 'publish', {title: 'Publish'});
		registry.insertAfter('menu/main/edit', 'preview', {title: 'Preview'});
		registry.append('menu/main', 'edit', {title: 'Edit'});
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);

		registry.compile();
		// TODO: here is still an error I think: "delete" should be the last element,
		// as "preview" should be inserted after "edit", and "publish" after "preview".
		// Thus, the order should be: "edit -> preview -> publish -> delete"
		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
					title: 'Preview',
					key: 'preview'
				}, {
					title: 'Publish',
					key: 'publish'
				}, {
					title: 'Delete',
					key: 'delete'
				}]
			}
		}));
	});

	it ("Compile with insert after appends missing keys and insert after unregistered keys.", function() {
		registry.insertAfter('menu/main/preview', 'publish', {title: 'Publish'});
		registry.insertAfter('menu/main/missing', 'afterDelete', {title: 'After Delete'});
		registry.insertAfter('menu/main/edit', 'preview', {title: 'Preview'});
		registry.append('menu/main', 'edit', {title: 'Edit'});
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
					title: 'Preview',
					key: 'preview'
				}, {
					title: 'Publish',
					key: 'publish'
				}, {
					title: 'Delete',
					key: 'delete'
				}, {
					title: 'After Delete',
					key: 'afterDelete'
				}]
			}
		}));
	});

	it ("Compile with insert before appends missing keys.", function() {
		registry.insertBefore('menu/main/preview', 'publish', {title: 'Publish'});
		registry.insertBefore('menu/main/delete', 'preview', {title: 'Preview'});
		registry.insertBefore('menu/main/delete', 'beforeDelete', {title: 'Before Delete'}, 20);
		registry.append('menu/main', 'edit', {title: 'Edit'});
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
				title: 'Publish',
					key: 'publish'
				}, {
					title: 'Preview',
					key: 'preview'
				}, {
					title: 'Before Delete',
					key: 'beforeDelete'
				}, {
					title: 'Delete',
					key: 'delete'
				}]
			}
		}));
	});

	it ("Compile with insert before appends missing keys and inserts before unregistered keys.", function() {
		registry.insertBefore('menu/main/preview', 'publish', {title: 'Publish'});
		registry.insertBefore('menu/main/delete', 'preview', {title: 'Preview'});
		registry.insertBefore('menu/main/missing', 'afterDelete', {title: 'After Delete'});
		registry.insertBefore('menu/main/delete', 'beforeDelete', {title: 'Before Delete'}, 20);
		registry.append('menu/main', 'edit', {title: 'Edit'});
		registry.append('menu/main', 'delete', {title: 'Delete'}, 10);

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
				title: 'Publish',
					key: 'publish'
				}, {
					title: 'Preview',
					key: 'preview'
				}, {
					title: 'Before Delete',
					key: 'beforeDelete'
				}, {
					title: 'Delete',
					key: 'delete'
				}, {
					title: 'After Delete',
					key: 'afterDelete'
				}]
			}
		}));
	});

	it ("Compile with append and path rewrite.", function() {
		registry.append('menu/main', 'content', {title: 'Content'});
		registry.append('menu/main/content[]', 'edit', {title: 'Edit'});

		registry.compile();

		expect(Ext.encode(registry.get())).toEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Content',
					children: [{
						title: 'Edit',
						key: 'edit'
					}],
					key: 'content'
				}]
			}
		}));
	});

	it ("Set works with really complex data", function() {
		var data = {
			'typo3:page': {
				service: {
					load: 'TYPO3.TYPO3.NodeService.load',
					store: 'TYPO3.TYPO3.NodeService.store'
				},
				properties: {
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
			}
		};

		registry.set('schema', data);
		registry.compile();

		expect(Ext.encode(registry.get('schema'))).toEqual(Ext.encode(data));
	});

	it ("Get works also with array parts", function() {
		registry.append('menu/main', 'content', {title: 'Content'});
		registry.append('menu/main/content[]', 'edit', {title: 'Edit'});

		registry.compile();

		expect(Ext.encode(registry.get('menu/main/content/children/edit'))).toEqual(Ext.encode({
			title: 'Edit',
			key: 'edit'
		}));

		expect(registry.get('notExistingPath')).toBeNull();
		expect(registry.get('menu/main/content/children/edit/children/edit')).toBeNull();
	});

});