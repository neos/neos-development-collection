import manifest from '@neos-project/neos-ui-extensibility';

import DimensionSwitcher from './DimensionSwitcher';

manifest('Neos.EventSourcedNeosAdjustments.Ui:UiAdapter', {}, globalRegistry => {
    const containerRegistry = globalRegistry.get('containers');

    containerRegistry.set('SecondaryToolbar/DimensionSwitcher', DimensionSwitcher);
});
