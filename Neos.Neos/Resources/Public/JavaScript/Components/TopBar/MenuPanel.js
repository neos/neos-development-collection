import Expandable from "./Expandable";
import { loadStorageData, saveStorageData } from "../../Services/LocalStorage";

const VALUE_PATH = "ui.drawer.collapsedMenuGroups";
export default class MenuPanel {
	constructor(_root) {
		this._root = _root;
		this._button = this._root.querySelectorAll(".neos-menu-button");
		this._panel = this._root.querySelectorAll(".neos-menu-panel");
		this._menuSectionStates = this._loadMenuSectionStates();
		this._setupEventListeners();

		if (this._panel) {
			this._initializeMenuSections();
		}
	}

	_initializeMenuSections() {
		this._panel.forEach(_panel => {
			const menuSectionElements = _panel.querySelectorAll(".neos-menu-section");
			const sections = this._menuSectionStates;
			menuSectionElements.forEach(menuSectionElement => {
				const sectionName = menuSectionElement.getAttribute("data-key");
				const sectionState = !sections.includes(sectionName);
				new Expandable(
					menuSectionElement,
					".neos-menu-panel-toggle",
					this._onMenuSectionStateChange.bind(this),
					sectionState
				);
			});
		});
	}

	_setupEventListeners() {
		this._button.forEach(_toggleButton => {
			_toggleButton.addEventListener("click", this._toggle.bind(this));
		});
	}

	_loadMenuSectionStates() {
		const storageData = loadStorageData(VALUE_PATH, []);
		return Array.isArray(storageData) ? storageData : [];
	}

	_saveMenuSectionStates() {
		if (Array.isArray(this._menuSectionStates)) {
			saveStorageData(VALUE_PATH, this._menuSectionStates);
		}
	}

	_onMenuSectionStateChange(sectionName, newValue) {
		if (this._menuSectionStates.includes(sectionName) && newValue === true) {
			this._menuSectionStates = this._menuSectionStates.filter(
				item => item !== sectionName
			);
		}

		if (!this._menuSectionStates.includes(sectionName) && newValue === false) {
			this._menuSectionStates.push(sectionName);
		}

		this._saveMenuSectionStates();
	}

	_toggle(_event) {
		this._button.forEach(_toggleButton => {
			_toggleButton.classList.toggle("neos-pressed");
		});
		document.body.classList.toggle("neos-menu-panel-open");
	}
}
