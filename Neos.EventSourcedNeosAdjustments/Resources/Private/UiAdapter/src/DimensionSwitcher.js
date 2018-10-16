import React, {PureComponent} from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Button, Icon, DropDown, SelectBox } from '@neos-project/react-ui-components';
import style from './style.css';
import backend from '@neos-project/neos-ui-backend-connector';
import {$map, $get, $transform} from 'plow-js';
import {Map} from 'immutable';
import {selectors, actions} from '@neos-project/neos-ui-redux-store';
import I18n from '@neos-project/neos-ui-i18n';
import sortBy from 'lodash.sortby';

// TODO Add title prop to Icon component
const SelectedPreset = props => {
    const {icon, dimensionLabel, presetLabel, dimensionName} = props;
    return (
        <span key={dimensionName} className={style.selectPreset}>
            <Icon className={style.dropDown__btnIcon} icon={icon} title={dimensionLabel}/>
            {presetLabel}
        </span>
    );
};
SelectedPreset.propTypes = {
    icon: PropTypes.string.isRequired,
    dimensionLabel: PropTypes.string.isRequired,
    presetLabel: PropTypes.string.isRequired,
    dimensionName: PropTypes.string.isRequired
};

const DimensionSelector = props => {
    const {icon, dimensionLabel, presets, dimensionName, activePreset, onSelect, isLoading} = props;

    const presetOptions = $map(
        (presetConfiguration, presetName) => {
            return $transform({
                label: $get('label'),
                value: presetName,
                disabled: $get('disabled')
            }, presetConfiguration);
        },
        [],
        presets
    ).toArray();

    const sortedPresetOptions = sortBy(presetOptions, ['label']);

    const onPresetSelect = presetName => {
        onSelect(dimensionName, presetName);
    };

    return (
        <li key={dimensionName} className={style.dimensionCategory}>
            <div className={style.dimensionLabel}>
                <Icon icon={icon} padded="right" className={style.dimensionCategory__icon}/>
                <I18n id={dimensionLabel}/>
            </div>
            <SelectBox
                displayLoadingIndicator={isLoading}
                options={sortedPresetOptions}
                onValueChange={onPresetSelect}
                value={activePreset}
            />
        </li>
    );
};
DimensionSelector.propTypes = {
    icon: PropTypes.string.isRequired,
    dimensionLabel: PropTypes.string.isRequired,
    presets: PropTypes.object.isRequired,
    activePreset: PropTypes.string.isRequired,
    dimensionName: PropTypes.string.isRequired,
    isLoading: PropTypes.bool,
    onSelect: PropTypes.func.isRequired
};

@connect($transform({
    contentDimensions: selectors.CR.ContentDimensions.byName,
    allowedPresets: selectors.CR.ContentDimensions.allowedPresets,
    activePresets: selectors.CR.ContentDimensions.activePresets
}), {
    selectPreset: actions.CR.ContentDimensions.selectPreset,
    setAllowed: actions.CR.ContentDimensions.setAllowed
})
export default class DimensionSwitcher extends PureComponent {
    static propTypes = {
        contentDimensions: PropTypes.object.isRequired,
        activePresets: PropTypes.object.isRequired,
        allowedPresets: PropTypes.object.isRequired,
        selectPreset: PropTypes.func.isRequired,
        setAllowed: PropTypes.func.isRequired
    };

    static defaultProps = {
        contentDimensions: new Map(),
        allowedPresets: new Map(),
        activePresets: new Map()
    };

    state = {
        isOpen: false,
        transientPresets: {},
        loadingPresets: {}
    };

    //
    // Merge active presets comming from redux with local transientPresets state (i.e. presents selected, but not yet applied)
    //
    getEffectivePresets = () => {
        const activePresets = this.props.activePresets.map(dimensionPreset => $get('value', dimensionPreset));
        return Object.assign({}, activePresets.toJS()/*, this.state.transientPresets*/); // TODO: re-introduce transient presets
    };

    handleSelectPreset = (selectedDimensionName, presetName) => {
        // If only one dimension commit right away; else store in the transient state
        if (this.props.contentDimensions.count() === 1) {
            this.setState({isOpen: false});
            this.props.selectPreset({[selectedDimensionName]: presetName});
        } else {
            const {contentDimensions} = backend.get().endpoints;
            const transientPresets = {
                ...this.state.transientPresets,
                [selectedDimensionName]: presetName
            };
            // First we update transientPresets in state, and only then we can get relevant effectivePresets
            this.setState({
                transientPresets
            }, () => {
                const effectivePresets = this.getEffectivePresets();
                Object.keys(effectivePresets).forEach(dimensionName => {
                    // No need to update constraints for just chosen dimension
                    if (dimensionName !== selectedDimensionName) {
                        // For each other dimension update constraints
                        this.setState({loadingPresets: {[dimensionName]: true}});
                        // Query backend API for updated constraints
                        contentDimensions(dimensionName, effectivePresets).then(dimensionConfig => {
                            const allowedPresets = Object.keys($get([dimensionName, 'presets'], dimensionConfig));
                            this.props.setAllowed(dimensionName, allowedPresets);
                            this.setState({loadingPresets: {[dimensionName]: false}});
                        });
                    }
                });
            });
        }
    }

    handleApplyPresets = () => {
        this.props.selectPreset(this.state.transientPresets);
        this.setState({isOpen: false, transientPresets: {}});
    }

    handleToggle = () => {
        this.setState({isOpen: !this.state.isOpen});
    }

    handleClose = () => {
        this.setState({isOpen: false});
    }

    render() {
        const {contentDimensions, activePresets} = this.props;
        const contentDimensionsObject = contentDimensions.toObject();
        const contentDimensionsObjectKeys = Object.keys(contentDimensionsObject);

        return contentDimensionsObjectKeys.length ? (
            <DropDown.Stateless
                style="darker"
                padded={true}
                className={style.dropDown}
                isOpen={this.state.isOpen}
                onToggle={this.handleToggle}
                onClose={this.handleClose}
            >
                <DropDown.Header>
                    {contentDimensionsObjectKeys.map(dimensionName => {
                        const dimensionConfiguration = contentDimensionsObject[dimensionName];
                        const icon = $get('icon', dimensionConfiguration) && $get('icon', dimensionConfiguration);
                        return (<SelectedPreset
                                key={dimensionName}
                                dimensionName={dimensionName}
                                icon={icon}
                                dimensionLabel={$get('label', dimensionConfiguration)}
                                presetLabel={$get([dimensionName, 'label'], activePresets)}
                            />
                        );
                    })}
                </DropDown.Header>
                <DropDown.Contents>
                    {contentDimensionsObjectKeys.map(dimensionName => {
                        const dimensionConfiguration = contentDimensionsObject[dimensionName];
                        const icon = $get('icon', dimensionConfiguration) && $get('icon', dimensionConfiguration);
                        // First look for active preset in transient state, else take it from activePresets prop
                        const activePreset = this.getEffectivePresets()[dimensionName];
                        return (<DimensionSelector
                                isLoading={this.state.loadingPresets[dimensionName]}
                                key={dimensionName}
                                dimensionName={dimensionName}
                                icon={icon}
                                dimensionLabel={$get('label', dimensionConfiguration)}
                                presets={this.valuesForDimension(dimensionName)}
                                activePreset={activePreset}
                                onSelect={this.handleSelectPreset}
                            />
                        );
                    })}
                    {contentDimensions.count() > 1 && <div className={style.buttonGroup}>
                        <Button
                            onClick={this.handleClose}
                            style="lighter"
                            className={style.cancelButton}
                        >
                            <I18n id="Neos.Neos:Main:cancel" fallback="Cancel"/>
                        </Button>
                        <Button
                            onClick={this.handleApplyPresets}
                            style="brand"
                        >
                            <I18n id="Neos.Neos:Main:apply" fallback="Apply"/>
                        </Button>
                    </div>}
                </DropDown.Contents>
            </DropDown.Stateless>
        ) : null;
    }

    valuesForDimension(dimensionName) {
        const {contentDimensions, allowedPresets} = this.props;
        const dimensionConfiguration = $get(dimensionName, contentDimensions);

        return dimensionConfiguration.get('values'); /*.map(
            (presetConfiguration, presetName) => allowedPresets.get(dimensionName) && allowedPresets.get(dimensionName).contains(presetName) ? presetConfiguration : presetConfiguration.set('disabled', true)
        );*/ // TODO
    }
}
