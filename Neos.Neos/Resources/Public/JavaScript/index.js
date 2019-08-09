import {DropDownMenu, MenuPanel} from './Components/TopBar'

const dropDownMenuElements = document.querySelectorAll('.neos-user-menu');
dropDownMenuElements.forEach(dropDownElement => {
	new DropDownMenu(dropDownElement);
});

const menuPanelElements = document.querySelectorAll('.neos-menu');
menuPanelElements.forEach(panelElement => {
	new MenuPanel(panelElement);
});


