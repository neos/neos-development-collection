import React, { createContext, ReactNode, useContext } from 'react';

type NotifyProviderValues = NeosNotification;

type NotifyProviderProps = {
    children: ReactNode;
    notificationApi: NotifyProviderValues;
};

export const NotifyContext = createContext(null);
export const useNotify = (): NotifyProviderValues => useContext(NotifyContext);

export const NotifyProvider = ({ notificationApi, children }: NotifyProviderProps) => {
    const error = (title: string, message = '') => notificationApi['error'](title, message);
    const warning = (title: string, message = '') => notificationApi['warning'](title, message);
    const ok = (title: string) => notificationApi['ok'](title);
    const info = (title: string) => notificationApi['info'](title);
    const notice = (title: string) => notificationApi['notice'](title);

    return <NotifyContext.Provider value={{ notice, error, ok, info, warning }}>{children}</NotifyContext.Provider>;
};
