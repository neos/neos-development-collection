import jQuery from "jquery";

import { DropDownMenu, MenuPanel, UserMenu } from "./Components/TopBar";
import { UserManagement } from './Module/Administration'
import DropDown from "./Components/DropDown";
import DropDownGroup from "./Components/DropDownGroup";
import Tree from "./Components/Tree";
import Modal from "./Components/Modal";
import { Configuration, Notification, Localization, Helper } from "./Services";
import { cachedFetch } from "./Services/ResourceCache";
import { isNil } from "./Helper";

// export jQuery globally
window.jQuery = jQuery;
window.$ = jQuery;

// init API's
Helper.init();
Configuration.init();
Notification.init();
Localization.init();

// preload vieSchema
const vieSchema = cachedFetch(Configuration.get("VieSchemaUri"));
cachedFetch(Configuration.get("XliffUri")).then((xliffData) => {
  if (xliffData) {
    Localization.initTranslations(xliffData);
  }
});

document.addEventListener("DOMContentLoaded", (event) => {

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
    const modalElement = document.querySelector(
      _modalTrigger.getAttribute("href")
    );
    if (!isNil(modalElement)) {
      new Modal(modalElement);
    }
  });

  const expandableElements = document.querySelectorAll(
    "[data-neos-expandable=dropdown]"
  );
  expandableElements.forEach((expandableElement) => {
    new DropDown(expandableElement);
  });

  const expandableGroupElements = document.querySelectorAll(
    "[data-neos-expandable=dropdown-group]"
  );
  expandableGroupElements.forEach((expandableElement) => {
    new DropDownGroup(expandableElement);
  });

  const userModuleContainer = document.querySelector('.neos-module-administration-users');
  if (!isNil(userModuleContainer)) {
    Array.from(userModuleContainer.querySelectorAll('.neos-table')).forEach(
        (_userModule) => {
          if (!isNil(_userModule)) {
            new UserManagement(_userModule);
          }
        }
    )
  }

  const userMenuContainer = document.querySelector('#neos-top-bar .neos-user-menu');
  if (!isNil(userMenuContainer)) {
    new UserMenu(userMenuContainer);
  }
});
