import jQuery from "jquery";

import { DropDownMenu, MenuPanel } from "./Components/TopBar";
import { UserManagement } from './Module/Administration';
import UserMenu from "./Components/UserMenu";
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

document.addEventListener("DOMContentLoaded", (event) => {
  initialize();
});

const initialize = () => {
  // init API's
  Helper.init();
  Configuration.init();
  Notification.init();
  Localization.init();

  cachedFetch(Configuration.get("XliffUri")).then((xliffData) => {
    if (xliffData) {
        Localization.initTranslations(xliffData);
    }
  });

  initializeMenuPanel();
  initializeDropDownMenus();
  initializeExpandableElements();
  initializeUserMenu();
  initializeUserManagement();
  initializeTree();
  initializeModal();
}
const initializeMenuPanel = () => {
  const menuPanelElements = document.querySelectorAll(".neos-menu");
  menuPanelElements.forEach((panelElement) => {
    new MenuPanel(panelElement);
  });
}
const initializeExpandableElements = () => {
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
}

const initializeDropDownMenus = () => {
  const dropDownMenuElements = document.querySelectorAll(".neos-user-menu");
  dropDownMenuElements.forEach((dropDownElement) => {
    new DropDownMenu(dropDownElement);
  });
}
const initializeTree = () => {
  const treeElements = document.querySelectorAll(".neos-tree-container");
  treeElements.forEach((treeElement) => {
    new Tree(treeElement);
  });
}
const initializeModal = () => {
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
}
const initializeUserManagement = () => {
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
}
const initializeUserMenu = () => {
  const userMenuContainer = document.querySelector('#neos-top-bar .neos-user-menu');
  if (!isNil(userMenuContainer)) {
    new UserMenu(userMenuContainer);
  }
}

window.addEventListener(
    'neos:neos:initialize',
    initialize,
    false
)

window.addEventListener(
    'neos:neos:initialize-menu-panel',
    initializeMenuPanel,
    false
)

window.addEventListener(
    'neos:neos:initialize-expandable-elements',
    initializeExpandableElements,
    false
)

window.addEventListener(
    'neos:neos:initialize-drop-down-menus',
    initializeDropDownMenus,
    false
)

window.addEventListener(
    'neos:neos:initialize-tree',
    initializeTree,
    false
)

window.addEventListener(
    'neos:neos:initialize-modal',
    initializeModal,
    false
)

window.addEventListener(
    'neos:neos:initialize-user-management',
    initializeUserManagement,
    false
)

window.addEventListener(
    'neos:neos:initialize-user-menu',
    initializeUserMenu,
    false
)
