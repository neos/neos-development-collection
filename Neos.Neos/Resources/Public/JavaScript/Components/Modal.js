import { isEmpty, isNil } from "../Helper";

export default class Modal {
	constructor(_root) {
		this._root = _root;
		this._triggers = Array.from(
			document.querySelectorAll(`[href="#${_root.id}"][data-toggle="modal"]`)
		);
		this._closeButtons = Array.from(
			this._root.querySelectorAll('[data-dismiss="modal"]')
		);
		this._header = _root.querySelector(".neos-header");
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

	_open(_event) {
		_event.preventDefault();
		const trigger = _event.target;
		this._handleDynamicHeader(trigger);

		this._root.classList.add("open");
		this._root.classList.remove("neos-hide");

		window.dispatchEvent(
			new CustomEvent("neoscms-modal-opened", {
				detail: { identifier: this._root.id },
			})
		);
	}

	_close() {
		this._root.classList.remove("open");
		this._root.classList.add("neos-hide");

		window.dispatchEvent(
			new CustomEvent("neoscms-modal-closed", {
				detail: { identifier: this._root.id },
			})
		);
	}

	_onKeyPress(_event) {
		if (_event.key === "Escape") {
			this._close();
		}
	}

	_handleDynamicHeader(_trigger) {
		if (isNil(_trigger) || isNil(this._header)) {
			return false;
		}

		if (!_trigger.hasAttribute("data-toggle")) {
			_trigger = _trigger.closest('[data-toggle="modal"]');
		}

		if (isNil(_trigger)) {
			return false;
		}

		const dynamicHeader = _trigger.getAttribute("data-modal-header");
		if (!isEmpty(dynamicHeader)) {
			this._header.innerText = dynamicHeader;
		}
	}
}
