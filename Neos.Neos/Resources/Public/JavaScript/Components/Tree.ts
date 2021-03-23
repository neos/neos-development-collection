import { isNil, isEmpty } from "../Helper";
import { loadStorageData, saveStorageData } from "../Services/LocalStorage";

const VALUE_PATH = "module.configuration";
export default class Tree {
  protected root?: HTMLElement;
  protected nodes?: Array<HTMLElement>;
  protected type?: string;
  protected treeBranchStates: Array<string>;

  constructor(_root: HTMLElement) {
    this.root = _root;
    this.type = this.root.getAttribute("data-type");
    this.treeBranchStates = this._loadTreeBranchStates(true);
    this.nodes = Array.from(this.root.querySelectorAll(".neos-tree-node"));
    this._initializeTree();
    this._setupEventListeners();
  }

  _initializeTree() {
    this.nodes.forEach((_node: HTMLElement) => {
      if (_node.firstChild.nodeName.toLowerCase() !== "ul") {
        // @ts-ignore
        this._wrapElementWithNodeTitle(_node.firstChild);
      }
      const hasSubnodes = _node.querySelectorAll(".neos-tree-node");
      if (
        this._isFolder(_node) &&
        !isNil(hasSubnodes) &&
        hasSubnodes.length > 0
      ) {
        const expandIcon = this._createExpandIcon();
        _node.insertBefore(expandIcon, _node.firstChild);
      }
    });
  }

  _initializeTreeState(items: Array<string>) {
    if (!Array.isArray(items)) {
      return;
    }

    items.forEach((_item) => {
      const node = this.root.querySelector(`[title="${_item}"`);
      if (!isNil(node)) {
        node.classList.add("neos-tree-open");
      }
    });
  }

  _setupEventListeners() {
    this.nodes.forEach((_node) => {
      _node.addEventListener("click", this._onNodeClick.bind(this));
    });
  }

  _onNodeClick(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    const target = <HTMLElement>event.target;
    const node: HTMLElement = target.closest(".neos-tree-node");
    if (!isNil(node)) {
      this._deselectCurrentActiveNode();
      node.classList.toggle("neos-tree-active");
    }

    if (this._isFolder(node)) {
      this._toggle(node);
    }
  }

  _deselectCurrentActiveNode() {
    this.root.querySelectorAll(".neos-tree-active").forEach((_node) => {
      _node.classList.remove("neos-tree-active");
    });
  }

  _createExpandIcon() {
    const expandIcon = document.createElement("span");
    expandIcon.classList.add("neos-tree-expander");
    return expandIcon;
  }

  _wrapElementWithNodeTitle(element: HTMLElement) {
    const nodeTitle = document.createElement("span");
    nodeTitle.classList.add("neos-tree-title");
    element.parentNode.insertBefore(nodeTitle, element);
    nodeTitle.appendChild(element);
  }

  _isFolder(node: HTMLElement) {
    return !isNil(node) && node.classList.contains("neos-tree-folder");
  }

  _toggle(node: HTMLElement) {
    node.classList.toggle("neos-tree-open");
    this._changeTreeBranchState(node.getAttribute("title"));
  }

  _getPathForType() {
    const path = VALUE_PATH + (!isEmpty(this.type) ? "." + this.type : "");
    return path.toLowerCase();
  }

  _loadTreeBranchStates(init: Boolean) {
    const pathWithType = this._getPathForType();
    const storageData = loadStorageData(pathWithType);
    if (!isNil(init) && init === true) {
      this._initializeTreeState(storageData);
    }

    this.treeBranchStates = Array.isArray(storageData) ? storageData : [];
    return this.treeBranchStates;
  }

  _saveTreeBranchStates() {
    const pathWithType = this._getPathForType();
    if (Array.isArray(this.treeBranchStates)) {
      saveStorageData(pathWithType, this.treeBranchStates);
    }
  }

  _changeTreeBranchState(path: string) {
    if (isEmpty(path) || !Array.isArray(this.treeBranchStates)) {
      return;
    }

    if (this.treeBranchStates.includes(path)) {
      this.treeBranchStates = this.treeBranchStates.filter(
        (item) => item !== path
      );
    } else {
      this.treeBranchStates.push(path);
    }

    this._saveTreeBranchStates();
  }
}
