import {
	isNil,
	getCollectionValueByPath,
	createCollectionByPath
} from "../Helper";

const STORAGE_KEY = "persistedState";

const getStorage = () => {
	const storage = localStorage.getItem(STORAGE_KEY);
	return JSON.parse(storage);
};

const loadStorageData = (path, defaultValue) => {
	const storage = getStorage();
	const storageData = getCollectionValueByPath(storage, path);
	if (isNil(storageData)) {
		const initialStorageData = createCollectionByPath(
			{},
			path,
			isNil(defaultValue) ? null : defaultValue
		);
		localStorage.setItem(STORAGE_KEY, JSON.stringify(initialStorageData));
		return getCollectionValueByPath(initialStorageData, path);;
	}

	return storageData;
};

const saveStorageData = (path, value) => {
	const storage = getStorage();
	const updatedStorageData = createCollectionByPath(storage, path, value);
	if (!isNil(updatedStorageData)) {
		localStorage.setItem(STORAGE_KEY, JSON.stringify(updatedStorageData));
	}
}

export { loadStorageData, saveStorageData };
