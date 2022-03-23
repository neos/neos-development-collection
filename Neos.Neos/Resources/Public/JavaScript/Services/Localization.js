import i18next from "i18next";

import {
  isNil,
  isEmpty,
  getCollectionValueByPath,
  createCollectionByPath,
} from "../Helper";

const DEFAULT_PACKAGE = "Neos.Neos";
const DEFAULT_SOURCE = "Main";
const EXISTING_NAMESPACES = [];

/**
 * Creates a namespace string from the neos package name and the source name.
 * The package name and source name comes from the xliff data and uses underscores instead of dots.
 *
 * @param {string} packageName Package name separated by _ from the xliff data
 * @param {string} sourceName Source name separated by _ from the xliff data
 * @returns {string}
 */
const getTransformedNamespace = (packageName, sourceName) => {
  const dottedPackageName = isEmpty(packageName)
    ? DEFAULT_PACKAGE
    : packageName.replace(/\_/g, ".");
  const dottedSourceName = isEmpty(sourceName)
    ? DEFAULT_SOURCE
    : sourceName.replace(/\_/g, ".");
  return dottedPackageName + "/" + dottedSourceName;
};

/**
 * Creates a namespace string from the neos package name and the source name.
 *
 * @param {string} packageName Package name
 * @param {string} sourceName Source name
 * @returns {string}
 */
const getNamespace = (packageName, sourceName) => {
  const dottedPackageName = isEmpty(packageName)
    ? DEFAULT_PACKAGE
    : packageName.trim();
  const dottedSourceName = isEmpty(sourceName)
    ? DEFAULT_SOURCE
    : sourceName.trim();
  return dottedPackageName + "/" + dottedSourceName;
};

/**
 * Returns the used locale of the current xliff URI
 *
 * @returns {string}
 */
const getCurrentLanguage = () => {
  const xliffUri = getCollectionValueByPath(
    window.NeosCMS,
    "Configuration.XliffUri"
  );
  if (isNil(xliffUri)) {
    return "";
  }
  const parameter = new URL(xliffUri).searchParams;
  return parameter.get("locale");
};

/**
 * Set the initialised value for the I18n API.
 * The parameter is available via window.NeosCMS.I18n.initialized
 *
 * @param {boolean} initialised
 * @returns {void}
 */
const setInitialized = (initialised) => {
  createCollectionByPath(
    window,
    "NeosCMS.I18n.initialized",
    Boolean(initialised)
  );
};

/**
 * The xliff data saves plurals as arrays. The i18next library need a flatt structure in the labels.
 * So we replace the arrays with new items and append to the label the index with a underscore.
 *
 * e.g.:
 * "key": "singular",
 * "key_plural": "plural",
 *
 * @param {object} translations
 * @returns {object}
 */
const flattenPluralItems = (translations) => {
  const translationKeys = Object.keys(translations);
  translationKeys.forEach((key) => {
    if (Array.isArray(translations[key])) {
      translations[key].forEach((pluralItem, index) => {
        let newKey = key;
        if (Number.isInteger(index) && index === 1) {
          newKey = `${key}_plural`;
        }
        translations[newKey] = pluralItem;
      });
    }
  });

  return translations;
};

/**
 * Collect and define the existing namespaces for the language resources from the xliff data
 *
 * @param {object} xliffData JSON object with xliff data
 * @returns {void}
 */
const initializeExistingNamespaces = (xliffData) => {
  if (isNil(xliffData)) {
    return false;
  }
  const packageNames = Object.keys(xliffData);
  packageNames.forEach((packageName) => {
    const Sources = Object.keys(xliffData[packageName]);
    Sources.forEach((sourceName) => {
      const namespace = getTransformedNamespace(packageName, sourceName);
      const translations = xliffData[packageName][sourceName];
      if (!isNil(translations)) {
        EXISTING_NAMESPACES.push({ name: namespace, initialized: false });
      }
    });
  });
};

/**
 * Checks if we have language namespaces that has not been added to the i18next resources.
 * Also fires the neoscms-i18n-initialized event when all resources are available.
 *
 * @returns {void}
 */
const checkInitialisedNamespaces = () => {
  const hasNonInitializedNamespaces =
    EXISTING_NAMESPACES.findIndex(
      (namespace) => namespace.initialized === false
    ) >= 0;

  if (!hasNonInitializedNamespaces) {
    setInitialized(true);

    window.dispatchEvent(
      new CustomEvent("neoscms-i18n-initialized", {
        bubbles: true,
      })
    );
  }

  return !hasNonInitializedNamespaces;
};

/**
 * Transforms the data structue of the xliff data to i18next namespaced resource bundles.
 * Therefore we replace the underscores in the package and source name with dots.
 *
 * Every source in a package will be a i18next namespace. The namespace will be package-name/source
 * e.g. "Neos.Neos/Main"
 *
 * @param {object} xliffData JSON object with xliff data
 * @returns {void}
 */
const transformAndAppendXliffData = (xliffData) => {
  const language = i18next.languages[0];
  if (isNil(xliffData)) {
    return false;
  }

  const packageNames = Object.keys(xliffData);

  i18next.store.on("added", (lng, ns) => {
    // set namespace as initialized
    EXISTING_NAMESPACES.find((entry) => entry.name === ns)[
      "initialized"
    ] = true;
    checkInitialisedNamespaces();
  });

  packageNames.forEach((packageName) => {
    const Sources = Object.keys(xliffData[packageName]);
    Sources.forEach((sourceName) => {
      const namespace = getTransformedNamespace(packageName, sourceName);
      const translations = xliffData[packageName][sourceName];
      if (!isNil(translations)) {
        i18next.addResourceBundle(
          language,
          namespace,
          flattenPluralItems(translations),
          true,
          true
        );
      }
    });
  });
};

/**
 * Returns a translated label.
 *
 * Replaces all placeholders with corresponding values if they exist in the
 * translated label.
 *
 * @param {string} id Id to use for finding translation (trans-unit id in XLIFF)
 * @param {string} fallback Fallback value in case the no label translation was found.
 * @param {string} packageKey Target package key. If not set, the current package key will be used
 * @param {string} source Name of file with translations
 * @param {object} parameters Numerically indexed array of values to be inserted into placeholders
 * @param {string} context
 * @param {number} quantity
 * @returns {string}
 */
const translate = (
  id,
  fallback,
  packageKey,
  source,
  parameters,
  context,
  quantity
) => {
  id = id.replace(/\./g, "_");
  const namespace = getNamespace(packageKey, source);
  const identifier = namespace + ":" + id.trim();

  let options = {};
  if (!isNil(quantity)) {
    options["count"] = quantity;
  }

  if (!isNil(parameters)) {
    options["replace"] = parameters;
  }

  if (!isEmpty(fallback)) {
    options["defaultValue"] = fallback;
  }

  return i18next.t(identifier, options);
};

const init = () => {
  if (isNil(window.NeosCMS)) {
    window.NeosCMS = {};
  }

  if (isNil(window.NeosCMS.I18n)) {
    window.NeosCMS.I18n = {
      init: init,
      translate: translate,
      initialized: false,
    };
  }
};

const initTranslations = (xliffData) => {
  // default options
  const options = {
    interpolation: {
      prefix: "{",
      suffix: "}",
    },
    resources: {},
  };

  // configure language
  const currentLangauge = getCurrentLanguage();
  if (!isEmpty(currentLangauge)) {
    // If the current language is not ISO-2 then we can not use the preferred language
    const languageOption = currentLangauge.match("[a-z]{2}(-[A-Z]{2})")
      ? "lng"
      : "fallbackLng";
    options[languageOption] = currentLangauge;
  }

  initializeExistingNamespaces(xliffData);

  // append translation resources
  i18next.init(options, (err, t) => {
    transformAndAppendXliffData(xliffData);
  });
};

export default { init, initTranslations, translate };
