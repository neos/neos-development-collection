/**
 * Get an item from sessionStorage
 *
 * @param {string} key Name of the value to get
 * @return {mixed} Depends on the stored value
 */
const getItem = (key) => {
	try {
		return JSON.parse(window.sessionStorage.getItem(key));
	} catch (e) {
		return undefined;
	}
};

/**
 * Set a value into session storage
 *
 * @param {string} key
 * @param {mixed} value
 * @return {void}
 */
const setItem = (key, value) => {
	try {
		window.sessionStorage.setItem(key, JSON.stringify(value));
	} catch (e) {
		// Clear the session storage in case an quota error is thrown
		window.sessionStorage.clear();
		window.sessionStorage.setItem(key, JSON.stringify(value));
	}
};

/**
 * Remove a value form session storage
 *
 * @param {string} key
 * @return {void}
 */
const removeItem = (key) => {
	window.sessionStorage.removeItem(key);
};

export default { getItem, setItem, removeItem };
