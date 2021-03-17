import { getCollectionValueByPath, isNil } from "../Helper";

const hasConfiguration =
	!isNil(window.NeosCMS) || !isNil(window.NeosCMS.Configuration);

const init = () => {
	if (isNil(window.NeosCMS)) {
		window.NeosCMS = {};
	}

	if (isNil(window.Typo3Neos)) {
		window.Typo3Neos = {};
	}

	if (isNil(window.NeosCMS.Configuration)) {
		window.NeosCMS.Configuration = {};
	}

	// append vie schema
	const schemaLink = document.querySelector('link[rel="neos-vieschema"]');
	if (!isNil(schemaLink)) {
		window.NeosCMS.Configuration.VieSchemaUri = schemaLink.getAttribute("href");
	}

	// append xliff uri
	const xliffLink = document.querySelector('link[rel="neos-xliff"]');
	if (!isNil(xliffLink)) {
		window.NeosCMS.Configuration.XliffUri = xliffLink.getAttribute("href");
	}

	// deprecated - to be removed in 8.0
	window.Typo3Neos.Configuration = window.NeosCMS.Configuration;
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
