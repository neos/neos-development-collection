import { isNil } from '.';

const getCollectionValueByPath = (collection, path) => {
	if (isNil(collection)) {
		return null;
	}

	return path.split('.').reduce(function(value, index) {
		return value[index];
	}, collection);
};

export default getCollectionValueByPath;
