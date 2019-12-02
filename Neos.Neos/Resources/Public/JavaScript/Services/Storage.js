import {
	isNil,
	getCollectionValueByPath,
	createCollectionByPath
} from "../Helper";

const STORAGE_KEY = "persistedState";

const getStorage = () => {
	const storage = localStorage.getItem(STORAGE_KEY);
	const storageJson = JSON.parse(storage);
	return isNil(storageJson) ? {} : storageJson;
};

const loadStorageData = (path) => {
	const storage = getStorage();
	const storageData = getCollectionValueByPath(storage, path);
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
