import DropDownMenu from './Components/DropdownMenu'

const dropDownMenuElements = document.querySelectorAll('.neos-user-menu');
dropDownMenuElements.forEach(element => {
	new DropDownMenu(element);
});


