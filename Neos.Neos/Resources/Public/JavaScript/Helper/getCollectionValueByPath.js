import { isNil } from '.';

const getCollectionValueByPath = (collection, path) => {
	if (isNil(collection)) {
		return null;
	}

	return path.split('.').reduce((value, index) => {
		if (isNil(value)) {
			return null;
		}
		return value[index];
	}, collection);
};

export default getCollectionValueByPath;
