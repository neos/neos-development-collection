export default interface NeosNotification {
  notice: (title: string) => void;
  ok: (title: string) => void;
  error: (title: string, message?: string) => void;
  warning: (title: string, message?: string) => void;
  info: (title: string) => void;
}
