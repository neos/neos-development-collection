Ext.ns("F3.TYPO3.Core");

F3.TYPO3.Core.RegistryTest = new YAHOO.tool.TestCase({

	name: "Test Registry",

	setUp: function() {
		this.registry = F3.TYPO3.Core.Registry;
		this.registry.initialize();
	},

	testSetSimpleValueAndDefaultPriority: function() {
		this.registry.set('foo', 'bar');

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testSetWithPriority: function() {
		this.registry.set('foo', 'bar', 50);

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testSetWithPath: function() {
		this.registry.set('foo/bar', 'baz');

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testSetRewritesObjectValueToSetWithPath: function() {
		this.registry.set('foo', {bar: 'baz', x: 'y'});

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testAppendCallsSetAndRegistersAppend: function() {
		this.registry.append('menu/main', 'edit', {title: 'Edit'});

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testPrependCallsSetAndRegistersPrepend: function() {
		this.registry.prepend('menu/main', 'edit', {title: 'Edit'}, 42);

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testInsertAfterAddsInsertOperation: function() {
		this.registry.insertAfter('menu/main/edit', 'preview', {title: 'Preview'});

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testInsertBeforeAddsInsertOperation: function() {
		this.registry.insertBefore('menu/main/edit', 'preview', {title: 'Preview'}, 50);

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testRemoveAddsRemoveOperation: function() {
		this.registry.remove('menu/main/preview', 60);

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.configuration));
	},
	testCompileConvertsSetWithPriority: function() {
		this.registry.set('foo', 'bar');
		this.registry.set('foo', 'baz', 10);

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			foo: 'baz'
		}), Ext.encode(this.registry.get()));
	},
	testCompileConvertsNestedSet: function() {
		this.registry.set('foo/bar', 'x', 50);
		this.registry.set('foo', {bar: 'baz', x: 'y'});

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			foo: {
				bar: 'x',
				x: 'y'
			}
		}), Ext.encode(this.registry.get()));
	},
	testCompileWithOverride: function() {
		this.registry.set('foo/bar', 'x');
		this.registry.set('foo', 'y', 50);

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			foo: 'y'
		}), Ext.encode(this.registry.get()));
	},
	testCompileRemovesValue: function() {
		this.registry.remove('foo/bar');
		this.registry.set('foo/bar', 'baz');

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			foo: {
				bar: undefined
			}
		}), Ext.encode(this.registry.get()));
	},
	testCompileWithMultipleAppend: function() {
		this.registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		this.registry.append('menu/main', 'edit', {title: 'Edit'});


		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
				}, {
					title: 'Delete',
					key: 'delete'
				}]
			}
		}), Ext.encode(this.registry.get()));
	},
	testGetWithPath: function() {
		this.registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		this.registry.append('menu/main', 'edit', {title: 'Edit'});


		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode(
			[{
				title: 'Edit',
				key: 'edit'
			}, {
				title: 'Delete',
				key: 'delete'
			}]
		), Ext.encode(this.registry.get('menu/main')));
	},
	testCompileWithMultipleAppendSetAndRemove: function() {
		this.registry.remove('menu/main/delete');
		this.registry.append('menu/main', 'delete', {title: 'Delete'}, 10);
		this.registry.set('menu/main/edit', {title: 'Bearbeiten'}, 10);
		this.registry.append('menu/main', 'edit', {title: 'Edit'});

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Bearbeiten',
					key: 'edit'
				}]
			}
		}), Ext.encode(this.registry.get()));
	},
	testCompileWitPrependAndAppend: function() {
		this.registry.append('menu/main', 'delete', {title: 'Delete'});
		this.registry.prepend('menu/main', 'edit', {title: 'Edit'}, 10);
		this.registry.prepend('menu/main', 'preview', {title: 'Preview'});

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.get()));
	},
	testCompileWithInsertAfterAppendsMissingKeys: function() {
		this.registry.insertAfter('menu/main/preview', 'publish', {title: 'Publish'});
		this.registry.insertAfter('menu/main/edit', 'preview', {title: 'Preview'});
		this.registry.append('menu/main', 'edit', {title: 'Edit'});
		this.registry.append('menu/main', 'delete', {title: 'Delete'}, 10);


		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
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
				}, {
					title: 'Publish',
					key: 'publish'
				}]
			}
		}), Ext.encode(this.registry.get()));
	},
	testCompileWithInsertBeforeAppendsMissingKeys: function() {
		this.registry.insertBefore('menu/main/preview', 'publish', {title: 'Publish'});
		this.registry.insertBefore('menu/main/delete', 'preview', {title: 'Preview'});
		this.registry.insertBefore('menu/main/delete', 'beforeDelete', {title: 'Before Delete'}, 20);
		this.registry.append('menu/main', 'edit', {title: 'Edit'});
		this.registry.append('menu/main', 'delete', {title: 'Delete'}, 10);

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
			menu: {
				main: [{
					title: 'Edit',
					key: 'edit'
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
					title: 'Publish',
					key: 'publish'
				}]
			}
		}), Ext.encode(this.registry.get()));
	},
	testCompileWithAppendAndPathRewrite: function() {
		this.registry.append('menu/main', 'content', {title: 'Content'});
		this.registry.append('menu/main/content[]', 'edit', {title: 'Edit'});

		this.registry.compile();

		YAHOO.util.Assert.areEqual(Ext.encode({
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
		}), Ext.encode(this.registry.get()));
	},
	testSetWorksWithReallyComplexData: function() {
		var data = {
			"typo3:page": {
				service: {
					load: 'F3.TYPO3.NodeService.load',
					store: 'F3.TYPO3.NodeService.store'
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
		this.registry.set('schema', data);
		this.registry.compile();
		YAHOO.util.Assert.areEqual(Ext.encode(data), Ext.encode(this.registry.get('schema')));
	}


});