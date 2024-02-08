import React, { createContext, ReactNode, useContext } from 'react';

type IntlProviderProps = {
    children: ReactNode;
    translate: TranslateFunction;
};

type IntlProviderValues = {
    translate: TranslateFunction;
};

export const IntlContext = createContext(null);
export const useIntl = (): IntlProviderValues => useContext(IntlContext);

export const IntlProvider = ({ translate, children }: IntlProviderProps) => {
    return <IntlContext.Provider value={translate}>{children}</IntlContext.Provider>;
};
