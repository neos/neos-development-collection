define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'InlineEditing/ContentCommands',
	'Content/Model/NodeSelection',
	'InlineEditing/InlineEditingHandles/ContentElementHandle',
	'InlineEditing/InlineEditingHandles/SectionHandle'
],
function(
	$,
	Ember,
	ContentCommands,
	NodeSelection,
	ContentElementHandle,
	SectionHandle
) {
	return Ember.View.extend({
		classNameBindings: ['isDocument:neos-hide'],
		classNames: ['neos-handle-container'],
		template: Ember.Handlebars.compile(
			'{{view view.ContentElementHandle isVisibleBinding="view.isContentElementBar"}}' +
			'{{view view.SectionHandle isVisibleBinding="view.isSectionBar"}}'
		),

		nodeSelection: NodeSelection,

		// Register views
		ContentElementHandle: ContentElementHandle,
		SectionHandle: SectionHandle,

		/**
		 * Returns true if the selected node is page
		 *
		 * @return {boolean}
		 */
		isDocument: function() {
			return this.get('_selectedNode') && ContentCommands.isDocument(this.get('_selectedNode'));
		}.property('_selectedNode'),

		/**
		 * Returns true if the selected node is not a section.
		 * This method does not take pages into account as the full bar is hidden if the node is a page
		 *
		 * @return {boolean}
		 */
		isContentElementBar: function() {
			return this.get('_selectedNode') && ContentCommands.isCollection(this.get('_selectedNode')) === false;
		}.property('_selectedNode'),

		/**
		 * Returns true if the selected node is a section
		 *
		 * @return {boolean}
		 */
		isSectionBar: function() {
			return this.get('_selectedNode') && ContentCommands.isCollection(this.get('_selectedNode'));
		}.property('_selectedNode'),

		/**
		 * Returns the current selected node in the NodeSelection.
		 *
		 * @return {Entity}
		 */
		_selectedNode: function() {
			return NodeSelection.get('selectedNode');
		}.property('nodeSelection.selectedNode')
	});
});