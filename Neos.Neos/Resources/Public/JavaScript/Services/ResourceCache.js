import { isNil } from "../Helper";
import SessionStorage from "./SessionStorage";

const fetchData = async (uri) => {
  const response = await fetch(uri);
  if (!response.ok) {
    // @todo Throw Notification
    throw new Error(`HTTP error! status: ${response.status}`);
  } else {
    return await response.json();
  }
};

/**
 * @param {string} resourceUri
 * @return {void}
 */
const cachedFetch = async (resourceUri) => {
  const cachedData = SessionStorage.getItem(resourceUri);
  const noCachedEntry = isNil(cachedData);
  if (isNil(resourceUri) && noCachedEntry) {
    return false;
  }

  if (noCachedEntry) {
    const responseData = await fetchData(resourceUri);
    SessionStorage.setItem(resourceUri, responseData);
    return responseData;
  } else {
    return cachedData;
  }
};

export { cachedFetch };
