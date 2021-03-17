import jQuery from "jquery";

import { DropDownMenu, MenuPanel } from "./Components/TopBar";
import Tree from "./Components/Tree";
import Modal from "./Components/Modal";
import { Configuration, Notification, Localisation } from "./Services";
import { cachedFetch } from "./Services/ResourceCache";
import { isNil } from "./Helper";

// export jQuery globally
window.jQuery = jQuery;
window.$ = jQuery;

// init API's
Configuration.init();
Notification.init();

// preload vieSchema
const vieSchema = cachedFetch(Configuration.get("VieSchemaUri"));
cachedFetch(Configuration.get("XliffUri")).then((xliffData) => {
	Localisation.init(xliffData);
});

const dropDownMenuElements = document.querySelectorAll(".neos-user-menu");
dropDownMenuElements.forEach((dropDownElement) => {
	new DropDownMenu(dropDownElement);
});

const menuPanelElements = document.querySelectorAll(".neos-menu");
menuPanelElements.forEach((panelElement) => {
	new MenuPanel(panelElement);
});

const treeElements = document.querySelectorAll(".neos-tree-container");
treeElements.forEach((treeElement) => {
	new Tree(treeElement);
});

const modalTrigger = Array.from(
	document.querySelectorAll('[data-toggle="modal"]')
);
modalTrigger.forEach((_modalTrigger) => {
	const modalElement = document.querySelector(_modalTrigger.getAttribute('href'));
	if (!isNil(modalElement)) {
		new Modal(modalElement);
	}
});
