import {isNil} from "../Helper"

const impersonateIcon = '<i class="fas fa-random icon-white"></i>'

const ImpersonateButton = (identifier, disabled) => {
		const localizedTooltip = !isNil(window.NeosCMS) ?
			window.NeosCMS.I18n.translate('impersonate.tooltip.impersonateUserButton', 'Login as this user', 'Neos.Neos') :
			'Login as this user';
    const attributesObject = {
        'data-neos-toggle': 'tooltip',
        'title': localizedTooltip,
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
