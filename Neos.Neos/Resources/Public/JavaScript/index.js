import {DropDownMenu, MenuPanel} from './Components/TopBar'
import Tree from './Components/Tree'

const dropDownMenuElements = document.querySelectorAll('.neos-user-menu');
dropDownMenuElements.forEach(dropDownElement => {
	new DropDownMenu(dropDownElement);
});

const menuPanelElements = document.querySelectorAll('.neos-menu');
menuPanelElements.forEach(panelElement => {
	new MenuPanel(panelElement);
});

const treeElements = document.querySelectorAll('.neos-tree-container');
treeElements.forEach(treeElement => {
	new Tree(treeElement);
	console.log(treeElement);
});


