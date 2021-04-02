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
    this.treeBranchStates = this.loadTreeBranchStates(true);
    this.nodes = Array.from(this.root.querySelectorAll(".neos-tree-node"));
    this.initializeTree();
    this.setupEventListeners();
  }

  private initializeTree(): void {
    this.nodes.forEach((_node: HTMLElement) => {
      if (_node.firstChild.nodeName.toLowerCase() !== "ul") {
        // @ts-ignore
        this.wrapElementWithNodeTitle(_node.firstChild);
      }
      const hasSubnodes = _node.querySelectorAll(".neos-tree-node");
      if (
        this.isFolder(_node) &&
        !isNil(hasSubnodes) &&
        hasSubnodes.length > 0
      ) {
        const expandIcon = this.createExpandIcon();
        _node.insertBefore(expandIcon, _node.firstChild);
      }
    });
  }

  private initializeTreeState(items: Array<string>): void {
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

  private setupEventListeners(): void {
    this.nodes.forEach((_node) => {
      _node.addEventListener("click", this.onNodeClick.bind(this));
    });
  }

  private onNodeClick(event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    const target = <HTMLElement>event.target;
    const node: HTMLElement = target.closest(".neos-tree-node");
    if (!isNil(node)) {
      this.deselectCurrentActiveNode();
      node.classList.toggle("neos-tree-active");
    }

    if (this.isFolder(node)) {
      this.toggle(node);
    }
  }

  private deselectCurrentActiveNode(): void {
    this.root.querySelectorAll(".neos-tree-active").forEach((_node) => {
      _node.classList.remove("neos-tree-active");
    });
  }

  private createExpandIcon(): HTMLElement {
    const expandIcon = document.createElement("span");
    expandIcon.classList.add("neos-tree-expander");
    return expandIcon;
  }

  private wrapElementWithNodeTitle(element: HTMLElement): void {
    const nodeTitle = document.createElement("span");
    nodeTitle.classList.add("neos-tree-title");
    element.parentNode.insertBefore(nodeTitle, element);
    nodeTitle.appendChild(element);
  }

  private isFolder(node: HTMLElement): boolean {
    return !isNil(node) && node.classList.contains("neos-tree-folder");
  }

  private toggle(node: HTMLElement): void {
    node.classList.toggle("neos-tree-open");
    this.changeTreeBranchState(node.getAttribute("title"));
  }

  private getPathForType(): string {
    const path = VALUE_PATH + (!isEmpty(this.type) ? "." + this.type : "");
    return path.toLowerCase();
  }

  private loadTreeBranchStates(init: Boolean) {
    const pathWithType = this.getPathForType();
    const storageData = loadStorageData(pathWithType);
    if (!isNil(init) && init === true) {
      this.initializeTreeState(storageData);
    }

    this.treeBranchStates = Array.isArray(storageData) ? storageData : [];
    return this.treeBranchStates;
  }

  private saveTreeBranchStates(): void {
    const pathWithType = this.getPathForType();
    if (Array.isArray(this.treeBranchStates)) {
      saveStorageData(pathWithType, this.treeBranchStates);
    }
  }

  private changeTreeBranchState(path: string): void {
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

    this.saveTreeBranchStates();
  }
}
