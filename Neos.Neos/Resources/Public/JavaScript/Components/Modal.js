import { isNil } from "../Helper";

export default class Modal {
	constructor(_root) {
		this._root = _root;
		this._triggers = Array.from(
			document.querySelectorAll(`[href="#${_root.id}"][data-toggle="modal"]`)
		);
		this._closeButtons = Array.from(
			this._root.querySelectorAll('[data-dismiss="modal"]')
		);
		this._setupEventListeners();
	}

	_setupEventListeners() {
		this._triggers.forEach((_trigger) => {
			_trigger.addEventListener("click", this._open.bind(this));
		});

		this._closeButtons.forEach((_closeButton) => {
			_closeButton.addEventListener("click", this._close.bind(this));
		});

		document.addEventListener("keyup", this._onKeyPress.bind(this));
	}

	_open() {
		this._root.classList.add("open");
		this._root.classList.remove("neos-hide");
	}

	_close() {
		this._root.classList.toggle("open");
		this._root.classList.toggle("neos-hide");
		if (!isNil(this._trigger)) {
			this._trigger.focus();
		}
	}

	_onKeyPress(_event) {
		if (_event.key === "Escape") {
			this._close();
		}
	}
}
