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
		const trigger = this._getTriggerElement(_event.target);

		this._handleDynamicHeader(trigger);
		this._root.classList.add("open");
		this._root.classList.remove("neos-hide");

		trigger.dispatchEvent(
			new CustomEvent("neoscms-modal-opened", {
				bubbles: true,
				detail: { identifier: this._root.id },
			})
		);
	}

	/**
	 * Trigger buttons can contain icons or text and so the event target is maybe not the mapping
	 * button element. So this function checks for the data-toggle attribute and if this does not
	 * exists we try to find the closest matching element.
	 *
	 * @param {object} _element
	 * @return {object}
	 */
	_getTriggerElement(_element) {
		if (isNil(_element)) {
			return null;
		}

		if (!_element.hasAttribute("data-toggle")) {
			_element = _element.closest('[data-toggle="modal"]');
		}
		return _element;
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

		const dynamicHeader = _trigger.getAttribute("data-modal-header");
		if (!isEmpty(dynamicHeader)) {
			this._header.innerText = dynamicHeader;
		}
	}
}
