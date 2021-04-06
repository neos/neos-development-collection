export default interface NeosConfiguration {
  init: () => void;
  get: (key: string) => string;
  override: (key: string, value: any) => void;
}
