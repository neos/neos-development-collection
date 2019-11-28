import { isNil } from '../Helper';

export default class Tree {
	constructor(_root) {
		this._root = _root;
		this._nodes = this._root.querySelectorAll('.neos-tree-node');
		this._initializeTree();
		this._setupEventListeners();
	}

	_initializeTree() {
		this._nodes.forEach(_node => {
			if (_node.firstChild.nodeName.toLowerCase() !== 'ul') {
				this._wrapElementWithNodeTitle(_node.firstChild);
			}
			if (this._isFolder(_node)) {
				const expandIcon = this._createExpandIcon();
				_node.insertBefore(expandIcon, _node.firstChild);
			}
		});
	}
	_setupEventListeners() {
		this._nodes.forEach(_node => {
			_node.addEventListener('click', this._onNodeClick.bind(this));
		});
	}

	_onNodeClick(event) {
		event.preventDefault();
		event.stopPropagation();
		const node = event.target.closest('.neos-tree-node');
		if (!isNil(node)) {
			this._deselectCurrentActiveNode();
			node.classList.toggle('neos-tree-active');
		}

		if (this._isFolder(node)) {
			this._toggle(node);
		}
	}

	_deselectCurrentActiveNode() {
		this._root.querySelectorAll('.neos-tree-active').forEach(_node => {
			_node.classList.remove('neos-tree-active');
		});
	}

	_createExpandIcon() {
		const expandIcon = document.createElement('span');
		expandIcon.classList.add('neos-tree-expander');
		return expandIcon;
	}

	_wrapElementWithNodeTitle(element) {
		const nodeTitle = document.createElement('span');
		nodeTitle.classList.add('neos-tree-title');
		element.parentNode.insertBefore(nodeTitle, element);
		nodeTitle.appendChild(element);
	}

	_isFolder(node) {
		return !isNil(node) && node.classList.contains('neos-tree-folder');
	}

	_toggle(node) {
		// @todo save node state in session
		node.classList.toggle('neos-tree-open');
	}
}
