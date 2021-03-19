import NeosI18n from "./NeosI18n";
import NeosNotification from "./NeosNotification";
import NeosConfiguration from "./NeosConfiguration";

export default interface Window {
  Typo3Neos: {
    I18n: NeosI18n;
    Notification: NeosNotification;
    Configuration: NeosConfiguration;
  };
  NeosCMS: {
    I18n: NeosI18n;
    Notification: NeosNotification;
    Configuration: NeosConfiguration;
  };
}
