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
    this.menuSectionStates = this.loadMenuSectionStates();
    this.setupEventListeners();

    if (this.panel) {
      this.initializeMenuSections();
    }
  }

  private initializeMenuSections(): void {
    this.panel.forEach((_panel) => {
      const menuSectionElements = _panel.querySelectorAll(".neos-menu-section");
      const sections = this.menuSectionStates;
      menuSectionElements.forEach((menuSectionElement) => {
        const sectionName = menuSectionElement.getAttribute("data-key");
        const sectionState = !sections.includes(sectionName);
        new Expandable(
          <HTMLElement>menuSectionElement,
          ".neos-menu-panel-toggle",
          this.onMenuSectionStateChange.bind(this),
          sectionState
        );
      });
    });
  }

  private setupEventListeners(): void {
    this.button.forEach((_toggleButton) => {
      _toggleButton.addEventListener("click", this.toggle.bind(this));
    });
  }

  private loadMenuSectionStates(): Array<any> {
    const storageData = loadStorageData(VALUE_PATH);
    return Array.isArray(storageData) ? storageData : [];
  }

  private saveMenuSectionStates(): void {
    if (Array.isArray(this.menuSectionStates)) {
      saveStorageData(VALUE_PATH, this.menuSectionStates);
    }
  }

  private onMenuSectionStateChange(
    sectionName: string,
    newValue: Boolean
  ): void {
    if (this.menuSectionStates.includes(sectionName) && newValue === true) {
      this.menuSectionStates = this.menuSectionStates.filter(
        (item: string) => item !== sectionName
      );
    }

    if (!this.menuSectionStates.includes(sectionName) && newValue === false) {
      this.menuSectionStates.push(sectionName);
    }

    this.saveMenuSectionStates();
  }

  private toggle(_event: Event): void {
    this.button.forEach((_toggleButton) => {
      _toggleButton.classList.toggle("neos-pressed");
    });
    document.body.classList.toggle("neos-menu-panel-open");
  }
}
