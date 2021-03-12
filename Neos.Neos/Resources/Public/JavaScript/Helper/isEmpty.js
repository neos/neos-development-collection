import isNil from "./isNil";

const isEmpty = (object) => {
	if (typeof object === "string") {
		return object.length === 0;
	}
	if (isNil(object)) {
		return true;
	}

	return (
		!Object.getOwnPropertySymbols(object).length &&
		!Object.getOwnPropertyNames(object).length
	);
};

export default isEmpty;
