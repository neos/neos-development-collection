import { isNil } from ".";

const getCollectionValueByPath = (collection: any, path: string) => {
	if (isNil(collection)) {
		return null;
	}

	return path.split(".").reduce((value, index) => {
		if (isNil(value)) {
			return null;
		}
		// @ts-ignore
		return value[index];
	}, collection);
};

export default getCollectionValueByPath;
