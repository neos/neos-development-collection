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

		// Clone template
		const template = document.getElementById(this.getAttribute("target"));
		const content = template.content.cloneNode(true);
		shadow.appendChild(content);

		const redispatch = (event) => {
			const cloned = new event.constructor(event.type);
			Object.defineProperty(cloned, "target", {
				value: event.target
			});
			document.dispatchEvent(cloned);
		}

		// the neos ui sets an event listener on the document and will look into each events target.
		// when something is clicked inside the shadowRoot, the events target will only contain <neos-raw-content />,
		// but not the specific element inside <neos-raw-content />.
		// for this reason we attach a listener to the shadowRoot (where we receive the actual target) and redispatch the event on the main document
		shadow.addEventListener('mousedown', redispatch);
		shadow.addEventListener('mouseup', redispatch);
		shadow.addEventListener('keyup', redispatch);

		const stopEvent = (/** @type {Event} */ event) => {
			event.stopImmediatePropagation();
		}

		// we stop the originally dispatched event from <neos-raw-content /> as the ui will be confused by it
		this.addEventListener('mousedown', stopEvent)
		this.addEventListener('mouseup', stopEvent)
		this.addEventListener('keyup', stopEvent)
	}

	connectedCallback() {
			this.render();
	}
}

if (customElements.get('neos-raw-content') === undefined) {
	customElements.define('neos-raw-content', Neos_RawContentMode);
}
