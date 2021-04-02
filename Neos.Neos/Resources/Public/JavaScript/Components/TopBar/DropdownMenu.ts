export default class DropDownMenu {
  protected root?: HTMLElement;
  protected button?: Array<HTMLElement>;
  protected menu?: Array<HTMLElement>;

  constructor(_root: HTMLElement) {
    this.root = _root;
    this.button = Array.from(
      this.root.querySelectorAll(".neos-dropdown-toggle")
    );
    this.menu = Array.from(this.root.querySelectorAll(".neos-dropdown-menu"));
    this.setupEventListeners();
  }

  private setupEventListeners(): void {
    this.button.forEach((_toggleButton: HTMLElement) => {
      _toggleButton.addEventListener("click", this.toggle.bind(this));
    });
  }

  private toggle(_event: Event): void {
    this.changeToogleIcon();
    this.root.classList.toggle("neos-dropdown-open");
  }

  private changeToogleIcon(): void {
    const openIcon: HTMLElement = this.root.querySelector(".fa-chevron-down");
    const closeIcon: HTMLElement = this.root.querySelector(".fa-chevron-up");
    if (openIcon) {
      openIcon.classList.replace("fa-chevron-down", "fa-chevron-up");
    }
    if (closeIcon) {
      closeIcon.classList.replace("fa-chevron-up", "fa-chevron-down");
    }
  }
}
