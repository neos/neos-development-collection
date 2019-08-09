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
		this._root.classList.toggle('neos-open');
	}

	_changeToogleIcon() {
		const openIcon = this._root.querySelector(`.fa-caret-down`);
		const closeIcon = this._root.querySelector(`.fa-caret-up`);
		if (openIcon) {
			openIcon.classList.remove('fa-caret-down');
			openIcon.classList.add('fa-caret-up');
		}
		if (closeIcon) {
			closeIcon.classList.remove('fa-caret-up');
			closeIcon.classList.add('fa-caret-down');
		}
	}
}
