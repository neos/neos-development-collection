/**
 * WARNING: if changing any of the statements below, make sure to also
 * update them inside ContentModuleBootstrap.js!
 *
 * To start a build, run 'grunt requirejs' from within the current directory.
 */
requirejs({
	baseUrl: '../Resources/Public/JavaScript/',
	paths: {
		'Library': '../Library/',
		'text': '../Library/requirejs/text',
		'i18n': '../Library/requirejs/i18n'
	},
	locale: 'en',

	name: 'ContentModuleBootstrap',
	include: [
		// The editors below are lazily loaded through Require.js, so we need to include them in the build manually.
		'Content/Inspector/Editors/TextFieldEditor',
		'Content/Inspector/Editors/BooleanEditor',
		'Content/Inspector/Editors/DateTimeEditor',
		'Content/Inspector/Editors/FileUpload',
		'Content/Inspector/Editors/CodeEditor',
		'Content/Inspector/Editors/ImageEditor',
		'Content/Inspector/Editors/MasterPluginEditor',
		'Content/Inspector/Editors/PluginViewEditor',
		'Content/Inspector/Editors/PluginViewsEditor',
		'Content/Inspector/Editors/ReferenceEditor',
		'Content/Inspector/Editors/ReferencesEditor',
		'Content/Inspector/Editors/SelectBoxEditor',
		'Content/Inspector/Editors/Aloha/AlohaButtonEditor',
		'Content/Inspector/Editors/Aloha/AlohaNonDefinedEditor',
		'Content/Inspector/Editors/Aloha/AlohaToggleButtonEditor',

		// The validators below are lazily loaded through Require.js, so we need to include them in the build manually.
		'Shared/Validation/AbstractValidator',
		'Shared/Validation/AlphanumericValidator',
		'Shared/Validation/CountValidator',
		'Shared/Validation/DateTimeRangeValidator',
		'Shared/Validation/DateTimeValidator',
		'Shared/Validation/EmailAddressValidator',
		'Shared/Validation/FloatValidator',
		'Shared/Validation/IntegerValidator',
		'Shared/Validation/LabelValidator',
		'Shared/Validation/NotEmptyValidator',
		'Shared/Validation/NumberRangeValidator',
		'Shared/Validation/RegularExpressionValidator',
		'Shared/Validation/StringLengthValidator',
		'Shared/Validation/StringValidator',
		'Shared/Validation/TextValidator',
		'Shared/Validation/UuidValidator'
	],
	out: '../Resources/Public/JavaScript/ContentModule-built.js'

	/**
	 * To generate a source map in development context, uncomment the lines below
	 */
//	,
//	generateSourceMaps: true,
//	preserveLicenseComments: false,
//	optimize: 'uglify2'
})