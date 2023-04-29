import { isNil } from ".";

const getItemByKeyValue = (collection: any, key: string, value: any) => {
  if (isNil(collection)) {
    return null;
  }
  // @ts-ignore
  return collection.find((object) => object[key] === value);
};

export default getItemByKeyValue;
