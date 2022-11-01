import { getCollectionValueByPath, isNil } from "../Helper";

const hasConfiguration = !isNil(window.NeosCMS?.Configuration);

const init = () => {
  if (isNil(window.NeosCMS)) {
    window.NeosCMS = {};
  }

  if (isNil(window.NeosCMS.Configuration)) {
    window.NeosCMS.Configuration = {};
  }

  // append xliff uri
  const xliffLink = document.querySelector('link[rel="neos-xliff"]');
  if (!isNil(xliffLink)) {
    window.NeosCMS.Configuration.XliffUri = xliffLink.getAttribute("href");
  }
};

const get = (key) => {
  if (!hasConfiguration) {
    return null;
  }

  return getCollectionValueByPath(window.NeosCMS.Configuration, key);
};

const override = (key, value) => {
  if (hasConfiguration && key in window.NeosCMS.Configuration) {
    window.NeosCMS.Configuration[key] = value;
  }
};

export default { init, get, override };
