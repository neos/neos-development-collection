import {isNil} from "../Helper"

const impersonateIcon = '<i class="fas fa-random icon-white"></i>'
const localizedTooltip = !isNil(window.Typo3Neos) ?
    window.Typo3Neos.I18n.translate('tooltip.impersonateUserButton', 'Login as this user', 'Unikka.LoginAs') :
    'Login as this user';

const ImpersonateButton = (identifier, disabled) => {
    const attributesObject = {
        'data-neos-toggle': 'tooltip',
        'data-original-title': localizedTooltip,
        'data-user-identifier': identifier,
        class: 'neos-button neos-button-primary impersonate-user',
    }

    if (!isNil(disabled) && disabled === true) {
        attributesObject.disabled = true
        attributesObject.class += ' neos-disabled'
    }

    let attributes = ''
    Object.keys(attributesObject).forEach(key => {
        attributes += `${key}="${attributesObject[key]}" `
    })

    return `<button ${attributes}>${impersonateIcon}</button>`
}

export {ImpersonateButton}
