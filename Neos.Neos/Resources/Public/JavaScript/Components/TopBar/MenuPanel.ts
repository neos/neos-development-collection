import Expandable from "./Expandable";
import { loadStorageData, saveStorageData } from "../../Services/LocalStorage";

const VALUE_PATH: string = "ui.drawer.collapsedMenuGroups";
export default class MenuPanel {
  protected root?: HTMLElement;
  protected button?: Array<HTMLElement>;
  protected panel?: Array<HTMLElement>;
  protected onStateChange?: Function;
  protected menuSectionStates: Array<string>;

  constructor(_root: HTMLElement) {
    this.root = _root;
    this.button = Array.from(this.root.querySelectorAll(".neos-menu-button"));
    this.panel = Array.from(this.root.querySelectorAll(".neos-menu-panel"));
    this.menuSectionStates = this._loadMenuSectionStates();
    this._setupEventListeners();

    if (this.panel) {
      this._initializeMenuSections();
    }
  }

  _initializeMenuSections() {
    this.panel.forEach((_panel) => {
      const menuSectionElements = _panel.querySelectorAll(".neos-menu-section");
      const sections = this.menuSectionStates;
      menuSectionElements.forEach((menuSectionElement) => {
        const sectionName = menuSectionElement.getAttribute("data-key");
        const sectionState = !sections.includes(sectionName);
        new Expandable(
          <HTMLElement>menuSectionElement,
          ".neos-menu-panel-toggle",
          this._onMenuSectionStateChange.bind(this),
          sectionState
        );
      });
    });
  }

  _setupEventListeners() {
    this.button.forEach((_toggleButton) => {
      _toggleButton.addEventListener("click", this._toggle.bind(this));
    });
  }

  _loadMenuSectionStates() {
    const storageData = loadStorageData(VALUE_PATH);
    return Array.isArray(storageData) ? storageData : [];
  }

  _saveMenuSectionStates() {
    if (Array.isArray(this.menuSectionStates)) {
      saveStorageData(VALUE_PATH, this.menuSectionStates);
    }
  }

  _onMenuSectionStateChange(sectionName: string, newValue: Boolean) {
    if (this.menuSectionStates.includes(sectionName) && newValue === true) {
      this.menuSectionStates = this.menuSectionStates.filter(
        (item: string) => item !== sectionName
      );
    }

    if (!this.menuSectionStates.includes(sectionName) && newValue === false) {
      this.menuSectionStates.push(sectionName);
    }

    this._saveMenuSectionStates();
  }

  _toggle(_event: Event) {
    this.button.forEach((_toggleButton) => {
      _toggleButton.classList.toggle("neos-pressed");
    });
    document.body.classList.toggle("neos-menu-panel-open");
  }
}
