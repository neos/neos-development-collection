class Neos_RawContentMode extends HTMLElement {

	constructor() {
		super();
	}

	render() {

		// Create a shadow root
		const shadow = this.attachShadow({mode: 'open'});

		// Create styles
		const styles = this.getAttribute("styles");
		if (styles) {
			const styleItems = styles.split(",");
			for (var styleItem in styleItems) {
				const link = document.createElement("link");
				link.setAttribute("rel", "stylesheet");
				link.setAttribute("href", styleItems[styleItem]);
				shadow.appendChild(link);
			}
		}

		// Render slot
		const slot = document.createElement('slot');
		shadow.appendChild(slot);
	}

	connectedCallback() {
			this.render();
	}
}

if (customElements.get('neos-raw-content') === undefined) {
	customElements.define('neos-raw-content', Neos_RawContentMode);
}
