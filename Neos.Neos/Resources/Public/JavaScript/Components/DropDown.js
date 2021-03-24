import { isEmpty, isNil } from "../Helper";

export default class DropDown {
	constructor(_root, _triggerElement, _grouped) {
		const _trigger = !isNil(_triggerElement)
			? _triggerElement
			: _root.querySelector(".neos-dropdown-trigger");
		const contentSelector = this._getContentSelector(_trigger);

		this._root = _root;
		this._trigger = _trigger;
		this._content = !isEmpty(contentSelector)
			? document.getElementById(contentSelector)
			: _root.querySelector(".neos-dropdown-content");
		this._grouped = isNil(_grouped) ? false : Boolean(_grouped);
		this._disabled = false;
		this._initialize();
		this._setupEventListeners();
	}

	/**
	 * Disabled the drop down trigger if we have no content
	 * or an empty content container.
	 *
	 * @returns {void}
	 */
	_initialize() {
		if (isNil(this._content)) {
			return false;
		}

		const innerContent = this._content.innerHTML.trim();
		if (isEmpty(innerContent)) {
			this._trigger.setAttribute("disabled", true);
			this._disabled = true;
		}
	}

	/**
	 * Returns an id that represents the content of the drop down.
	 * If the trigger is not valid we just return an empty string.
	 *
	 * @param {HTMLElement} _element
	 * @returns {string}
	 */
	_getContentSelector(_element) {
		return !isNil(_element) ? _element.getAttribute("aria-controls") : "";
	}

	_setupEventListeners() {
		if (!isNil(this._trigger) && !isNil(this._content) && !this._disabled) {
			this._trigger.addEventListener("click", this._toggle.bind(this));
		}
	}

	/**
	 * Toggle the drop down element. If the drop down is part of a drop down group
	 * all other drop downs will be closed first.
	 *
	 * @param {Event} _event
	 * @returns {void}
	 */
	_toggle(_event) {
		_event.preventDefault();
		if (this._grouped) {
			// close all other content panes when the dropdown is part of a button group
			this._closeOthers();
		} else {
			this._root.classList.toggle("open");
		}

		// get current state of the trigger
		const _triggerState = this._trigger.getAttribute("aria-expanded");
		const _state = !isEmpty(_triggerState)
			? _triggerState.toLowerCase() === "true"
			: false;

		if (_state === false) {
			this._open();
		} else {
			this._close(this._trigger);
		}
	}

	/**
	 * Opens drop down.
	 *
	 * @returns {void}
	 */
	_open() {
		this._trigger.setAttribute("aria-expanded", true);
		this._content.removeAttribute("hidden");
	}

	/**
	 * Closes the current drop down element or if the given trigger is not from
	 * the current drop down, it closes the associated drop down.
	 *
	 * @returns {void}
	 */
	_close(_trigger) {
		const _contentSelector = this._getContentSelector(_trigger);
		let _content = document.getElementById(_contentSelector);
		if (isNil(_content)) {
			_content = this._content;
		}

		// close elements
		_trigger.setAttribute("aria-expanded", false);
		_content.setAttribute("hidden", true);
	}

	/**
	 * Is fired when the drop down component is an grouped item.
	 * So the drop down acts like an accordeon.
	 *
	 * @returns {void}
	 */
	_closeOthers() {
		const dropDownElements = Array.from(
			this._root.querySelectorAll(".neos-dropdown-trigger")
		);
		dropDownElements.forEach((_element) => {
			if (_element !== this._trigger) {
				this._close(_element);
			}
		});
	}
}
