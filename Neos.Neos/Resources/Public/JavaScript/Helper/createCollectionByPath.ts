/**
 * The function can be used to create objects or change values by path.
 * So if you give an empty collection like {} with a path like a.b.c
 * and a value, you get and object {a: {b: {c: 'value' } } } as response.
 *
 * You can also pass thru the collection  {a: {b: {c: 'value' } } } and change
 * a.b.c to newValue. So you get  {a: {b: {c: 'newValue' } } }.
 *
 * If you use numerals as key we expect that the value should be an array
 * instead of an object.
 *
 * @param {object} collection
 * @param {string} path
 * @param {mixed} value
 * @return {object}
 */
const createCollectionByPath = (collection: any, path: string, value: any) => {
  collection = typeof collection === "object" ? collection : {};
  const keys = Array.isArray(path) ? path : path.split(".");
  let currentStep: object = collection;
  for (let i = 0; i < keys.length - 1; i++) {
    const key = keys[i];
    if (
      // @ts-ignore
      !currentStep[key] &&
      !Object.prototype.hasOwnProperty.call(currentStep, key)
    ) {
      const nextKey = keys[i + 1];
      const useArray = /^\+?(0|[1-9]\d*)$/.test(nextKey);
      // @ts-ignore
      currentStep[key] = useArray ? [] : {};
    }
    // @ts-ignore
    currentStep = currentStep[key];
  }
  const finalStep = keys[keys.length - 1];
  // @ts-ignore
  currentStep[finalStep] = value;

  return collection;
};

export default createCollectionByPath;
