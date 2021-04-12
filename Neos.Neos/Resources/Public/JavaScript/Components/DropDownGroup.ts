import { isNil } from "../Helper";
import DropDown from "./DropDown";

export default class DropDownGroup {
  protected root?: HTMLElement;

  constructor(_root: HTMLElement) {
    this.root = _root;
    this.initialize();
  }

  private initialize(): void {
    if (isNil(this.root)) {
      return;
    }

    const dropDownElements = Array.from(
      this.root.querySelectorAll(".neos-dropdown-trigger")
    );
    dropDownElements.forEach((_element) => {
      new DropDown(this.root, <HTMLElement>_element, true);
    });
  }
}
