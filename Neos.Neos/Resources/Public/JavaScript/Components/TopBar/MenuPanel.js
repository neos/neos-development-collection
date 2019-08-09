export default class MenuPanel {
	constructor(_root) {
		this._root = _root;
		this._button = this._root.querySelectorAll('.neos-menu-button');
		this._panel = this._root.querySelectorAll('.neos-menu-panel');
		this._setupEventListeners();
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
