import Expandable from './Expandable';
import { isNil, getItemByKeyValue } from '../../Helper';

const SESSION_KEY = 'Neos.Neos.menuSectionStates';
export default class MenuPanel {
	constructor(_root) {
		this._root = _root;
		this._button = this._root.querySelectorAll('.neos-menu-button');
		this._panel = this._root.querySelectorAll('.neos-menu-panel');
		this._menuSectionStates = this._loadSessionData();
		this._setupEventListeners();

		if (this._panel) {
			this._initializeMenuSections();
		}
	}

	_initializeMenuSections() {
		this._panel.forEach(_panel => {
			const menuSectionElements = _panel.querySelectorAll('.neos-menu-section');
			const { sections } = this._menuSectionStates;
			menuSectionElements.forEach(menuSectionElement => {
				const sectionName = menuSectionElement.getAttribute('data-key');
				const sectionState = getItemByKeyValue(sections, 'name', sectionName);
				const initalState = !isNil(sectionState) ? sectionState.open : false;
				new Expandable(
					menuSectionElement,
					'.neos-menu-panel-toggle',
					this._onMenuSectionStateChange.bind(this),
					initalState
				);
			});
		});
	}

	_setupEventListeners() {
		this._button.forEach(_toggleButton => {
			_toggleButton.addEventListener('click', this._toggle.bind(this));
		});
	}

	_loadSessionData() {
		const sessionData = sessionStorage.getItem(SESSION_KEY);
		if (isNil(sessionData)) {
			const initialSessionData = { sections: [] };
			sessionStorage.setItem(SESSION_KEY, JSON.stringify(initialSessionData));
			return initialSessionData;
		}

		return JSON.parse(sessionData);
	}

	_onMenuSectionStateChange(sectionName, newValue) {
		const { sections } = this._menuSectionStates;
		const sectionState = getItemByKeyValue(sections, 'name', sectionName);
		if (isNil(sections)) {
			this._menuSectionStates.sections = [];
		}

		if (isNil(sectionState)) {
			this._menuSectionStates.sections.push({
				name: sectionName,
				open: newValue
			});
		} else {
			sectionState.open = newValue;
		}

		sessionStorage.setItem(
			SESSION_KEY,
			JSON.stringify(this._menuSectionStates)
		);
	}

	_toggle(_event) {
		this._button.forEach(_toggleButton => {
			_toggleButton.classList.toggle('neos-pressed');
		});
		document.body.classList.toggle('neos-menu-panel-open');
	}
}
