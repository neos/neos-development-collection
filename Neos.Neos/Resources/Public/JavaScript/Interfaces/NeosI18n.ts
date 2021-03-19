export default interface NeosI18n {
  translate: (id: string, fallback: string, packageKey: string, source: string, args: any[]) => string;
}
