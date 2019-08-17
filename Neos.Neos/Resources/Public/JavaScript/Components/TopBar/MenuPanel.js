import Expandable from './Expandable';

export default class MenuPanel {
	constructor(_root) {
		this._root = _root;
		this._button = this._root.querySelectorAll('.neos-menu-button');
		this._panel = this._root.querySelectorAll('.neos-menu-panel');
		this._setupEventListeners();

		if (this._panel) {
			this._initializeMenuSections();
		}
	}

	_initializeMenuSections() {
		this._panel.forEach(_panel => {
			const menuSectionElements = _panel.querySelectorAll('.neos-menu-section');
			menuSectionElements.forEach(menuSectionElement => {
				new Expandable(menuSectionElement, '.neos-menu-panel-toggle');
			});
		});
	}

	_setupEventListeners() {
		this._button.forEach(_toggleButton => {
			_toggleButton.addEventListener('click', this._toggle.bind(this));
		});
	}

	_toggle(_event) {
		this._button.forEach(_toggleButton => {
			_toggleButton.classList.toggle('neos-pressed');
		});
		document.body.classList.toggle('neos-menu-panel-open');
	}
}
