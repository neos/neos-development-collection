export default class DropDownMenu {
	constructor(_root) {
		this._root = _root;
		this._button = this._root.querySelectorAll('.neos-dropdown-toggle');
		this._menu = this._root.querySelectorAll('.neos-dropdown-menu');
		this._setupEventListeners();
	}

	_setupEventListeners() {
		this._button.forEach(_toggleButton => {
			_toggleButton.addEventListener('click', this._toggle.bind(this));
		});
	}

	_toggle(_event) {
		this._changeToogleIcon();
		this._root.classList.toggle('neos-dropdown-open');
	}

	_changeToogleIcon() {
		const openIcon = this._root.querySelector('.fa-chevron-down');
		const closeIcon = this._root.querySelector('.fa-chevron-up');
		if (openIcon) {
			openIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
		}
		if (closeIcon) {
			closeIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
		}
	}
}
