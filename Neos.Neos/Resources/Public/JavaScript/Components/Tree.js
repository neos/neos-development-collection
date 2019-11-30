import { isNil, isEmpty } from "../Helper";
import { loadStorageData, saveStorageData } from "../Services/Storage";

const VALUE_PATH = "module.configuration";
export default class Tree {
	constructor(_root) {
		this._root = _root;
		this._type = this._root.getAttribute("data-type");
		this._treeBranchStates = this._loadTreeBranchStates(true);
		this._nodes = this._root.querySelectorAll(".neos-tree-node");
		this._initializeTree();
		this._setupEventListeners();
	}

	_initializeTree() {
		this._nodes.forEach(_node => {
			if (_node.firstChild.nodeName.toLowerCase() !== "ul") {
				this._wrapElementWithNodeTitle(_node.firstChild);
			}
			const hasSubnodes = _node.querySelectorAll(".neos-tree-node");
			if (this._isFolder(_node) && !isNil(hasSubnodes) && hasSubnodes.length > 0) {
				const expandIcon = this._createExpandIcon();
				_node.insertBefore(expandIcon, _node.firstChild);
			}
		});
	}

	_initializeTreeState(items) {
		if (!Array.isArray(items)) {
			return;
		}

		items.forEach(_item => {
			const node = this._root.querySelector(`[title="${_item}"`);
			if (!isNil(node)) {
				node.classList.add("neos-tree-open");
			}
		});
	}

	_setupEventListeners() {
		this._nodes.forEach(_node => {
			_node.addEventListener("click", this._onNodeClick.bind(this));
		});
	}

	_onNodeClick(event) {
		event.preventDefault();
		event.stopPropagation();
		const node = event.target.closest(".neos-tree-node");
		if (!isNil(node)) {
			this._deselectCurrentActiveNode();
			node.classList.toggle("neos-tree-active");
		}

		if (this._isFolder(node)) {
			this._toggle(node);
		}
	}

	_deselectCurrentActiveNode() {
		this._root.querySelectorAll(".neos-tree-active").forEach(_node => {
			_node.classList.remove("neos-tree-active");
		});
	}

	_createExpandIcon() {
		const expandIcon = document.createElement("span");
		expandIcon.classList.add("neos-tree-expander");
		return expandIcon;
	}

	_wrapElementWithNodeTitle(element) {
		const nodeTitle = document.createElement("span");
		nodeTitle.classList.add("neos-tree-title");
		element.parentNode.insertBefore(nodeTitle, element);
		nodeTitle.appendChild(element);
	}

	_isFolder(node) {
		return !isNil(node) && node.classList.contains("neos-tree-folder");
	}

	_toggle(node) {
		node.classList.toggle("neos-tree-open");
		this._changeTreeBranchState(
			node.getAttribute("title"),
			node.classList.contains("neos-tree-open")
		);
	}

	_getPathForType() {
		return VALUE_PATH + (!isEmpty(this._type) ? "." + this._type : "");
	}

	_loadTreeBranchStates(init) {
		const pathWithType = this._getPathForType();
		const storageData = loadStorageData(pathWithType, []);
		if (!isNil(init) && init === true) {
			this._initializeTreeState(storageData);
		}

		this._treeBranchStates = Array.isArray(storageData) ? storageData : [];
		return this._treeBranchStates;
	}

	_saveTreeBranchStates() {
		const pathWithType = this._getPathForType();
		if (Array.isArray(this._treeBranchStates)) {
			saveStorageData(pathWithType, this._treeBranchStates);
		}
	}

	_changeTreeBranchState(path) {
		if (isEmpty(path) || !Array.isArray(this._treeBranchStates)) {
			return;
		}

		if (this._treeBranchStates.includes(path)) {
			this._treeBranchStates = this._treeBranchStates.filter(
				item => item !== path
			);
		} else {
			this._treeBranchStates.push(path);
		}

		this._saveTreeBranchStates();
	}
}
