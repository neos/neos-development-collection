import { isNil } from "../Helper";
import DropDown from "./DropDown";

export default class DropDownGroup {
	constructor(_root) {
		this._root = _root;
		this._initialize();
	}

	_initialize() {
		if (isNil(this._root)) {
			return false;
		}

		const dropDownElements = Array.from(
			this._root.querySelectorAll(".neos-dropdown-trigger")
		);
		dropDownElements.forEach((_element) => {
			new DropDown(this._root, _element, true);
		});
	}
}
