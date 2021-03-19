export default class DropDownMenu {
  _root?: HTMLElement;
  _button?: Array<HTMLElement>;
  _menu?: Array<HTMLElement>;

  constructor(_root: HTMLElement) {
    this._root = _root;
    this._button = Array.from(this._root.querySelectorAll(".neos-dropdown-toggle"));
    this._menu = Array.from(this._root.querySelectorAll(".neos-dropdown-menu"));
    this._setupEventListeners();
  }

  _setupEventListeners() {
    this._button.forEach((_toggleButton: HTMLElement) => {
      _toggleButton.addEventListener("click", this._toggle.bind(this));
    });
  }

  _toggle(_event: Event) {
    this._changeToogleIcon();
    this._root.classList.toggle("neos-dropdown-open");
  }

  _changeToogleIcon() {
    const openIcon: HTMLElement = this._root.querySelector(".fa-chevron-down");
    const closeIcon: HTMLElement = this._root.querySelector(".fa-chevron-up");
    if (openIcon) {
      openIcon.classList.replace("fa-chevron-down", "fa-chevron-up");
    }
    if (closeIcon) {
      closeIcon.classList.replace("fa-chevron-up", "fa-chevron-down");
    }
  }
}
