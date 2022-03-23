import NeosI18n from "./NeosI18n";
import NeosNotification from "./NeosNotification";
import NeosConfiguration from "./NeosConfiguration";

export default interface Window {
  NeosCMS: {
    I18n: NeosI18n;
    Notification: NeosNotification;
    Configuration: NeosConfiguration;
  };
}
