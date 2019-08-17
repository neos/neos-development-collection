import isNil from './isNil';

const isEmpty = object => {
	if (isNil(object)) {
		return false;
	}

	return (
		!Object.getOwnPropertySymbols(object).length &&
		!Object.getOwnPropertyNames(object).length
	);
};

export default isEmpty;
