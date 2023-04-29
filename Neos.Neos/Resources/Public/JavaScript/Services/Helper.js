import {
  isNil,
  isEmpty,
  getItemByKeyValue,
  getCollectionValueByPath,
  createCollectionByPath,
} from "../Helper";

const init = () => {
  if (isNil(window.NeosCMS)) {
    window.NeosCMS = {};
  }

  if (isNil(window.Typo3Neos)) {
    window.Typo3Neos = {};
  }

  if (isNil(window.NeosCMS.Helper)) {
    window.NeosCMS.Helper = {
      isNil,
      isEmpty,
      getItemByKeyValue,
      getCollectionValueByPath,
      createCollectionByPath,
    };
  }

  // deprecated - to be removed in 8.0
  window.Typo3Neos.Helper = window.NeosCMS.Helper;
};

export default { init };
