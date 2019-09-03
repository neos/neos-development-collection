export default class Expandable {
	constructor(_root, _triggerClassName, _onStateChange, initialState) {
		this._root = _root;
		this._trigger = this._root.querySelectorAll(_triggerClassName);
		this._onStateChange = _onStateChange;
		this._setupEventListeners();
		this._initialize(initialState);
	}

	_setupEventListeners() {
		this._trigger.forEach(_toggleButton => {
			_toggleButton.addEventListener('click', this._toggle.bind(this));
		});
	}

	_initialize(initialState) {
		const header = this._root.querySelector('[aria-expanded]');
		header.setAttribute('aria-expanded', String(initialState));

		if (initialState) {
			// default is closed
			this._root.classList.add('neos-open');
			this._changeToogleIcon();
		}
	}

	_toggle() {
		this._changeToogleIcon();
		this._root.classList.toggle('neos-open');
		this._toogleAriaExpandable();
	}

	_toogleAriaExpandable() {
		const header = this._root.querySelector('[aria-expanded]');
		const expanded = this._root.classList.contains('neos-open');
		header.setAttribute('aria-expanded', String(expanded));
		if (typeof this._onStateChange === 'function') {
			const sectionName = this._root.getAttribute('data-key');
			this._onStateChange(sectionName, expanded);
		}
	}

	_changeToogleIcon() {
		const openIcon = this._root.querySelector('.fa-chevron-circle-down');
		const closeIcon = this._root.querySelector('.fa-chevron-circle-up');
		if (openIcon) {
			openIcon.classList.replace(
				'fa-chevron-circle-down',
				'fa-chevron-circle-up'
			);
		}
		if (closeIcon) {
			closeIcon.classList.replace(
				'fa-chevron-circle-up',
				'fa-chevron-circle-down'
			);
		}
	}
}
