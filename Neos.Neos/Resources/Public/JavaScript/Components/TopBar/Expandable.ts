export default class Expandable {
	protected root?: HTMLElement;
	protected trigger?: Array<HTMLElement>;
	protected onStateChange?: Function;

	constructor(
		_root: HTMLElement,
		_triggerClassName: string,
		_onStateChange: Function,
		initialState: Boolean
	) {
		this.root = _root;
		this.trigger = Array.from(this.root.querySelectorAll(_triggerClassName));
		this.onStateChange = _onStateChange;
		this.setupEventListeners();
		this.initialize(initialState);
	}

	private setupEventListeners(): void {
		this.trigger.forEach((_toggleButton) => {
			_toggleButton.addEventListener("click", this.toggle.bind(this));
		});
	}

	private initialize(initialState: Boolean): void {
		const header = this.root.querySelector("[aria-expanded]");
		header.setAttribute("aria-expanded", String(initialState));

		if (initialState) {
			// default is closed
			this.root.classList.add("neos-open");
			this.changeToogleIcon();
		}
	}

	private toggle(): void {
		this.changeToogleIcon();
		this.root.classList.toggle("neos-open");
		this.toogleAriaExpandable();
	}

	private toogleAriaExpandable(): void {
		const header = this.root.querySelector("[aria-expanded]");
		const expanded = this.root.classList.contains("neos-open");
		header.setAttribute("aria-expanded", String(expanded));
		if (typeof this.onStateChange === "function") {
			const sectionName = this.root.getAttribute("data-key");
			this.onStateChange(sectionName, expanded);
		}
	}

	private changeToogleIcon(): void {
		const openIcon = this.root.querySelector(".fa-chevron-circle-down");
		const closeIcon = this.root.querySelector(".fa-chevron-circle-up");
		if (openIcon) {
			openIcon.classList.replace(
				"fa-chevron-circle-down",
				"fa-chevron-circle-up"
			);
		}
		if (closeIcon) {
			closeIcon.classList.replace(
				"fa-chevron-circle-up",
				"fa-chevron-circle-down"
			);
		}
	}
}
