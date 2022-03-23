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

  if (isNil(window.Neos)) {
    window.Neos = {};
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
  window.Neos.Helper = window.NeosCMS.Helper;
};

export default { init };
