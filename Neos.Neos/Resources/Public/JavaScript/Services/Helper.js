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

  if (isNil(window.NeosCMS.Helper)) {
    window.NeosCMS.Helper = {
      isNil,
      isEmpty,
      getItemByKeyValue,
      getCollectionValueByPath,
      createCollectionByPath,
    };
  }
};

export default { init };
