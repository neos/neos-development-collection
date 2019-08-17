import { isNil } from '.';

const getItemByKeyValue = (collection, key, value) => {
	if (isNil(collection)) {
		return null;
	}
	return collection.find(object => object[key] === value);
};

export default getItemByKeyValue;
