import { isNil } from '../Helper'

const impersonateIcon = '<i class="fas fa-random icon-white"></i>'

const RestoreButton = (user) => {
    const attributesObject = {
        class: 'neos-button restore-user',
    }

    let attributes = ''
    Object.keys(attributesObject).forEach((key) => {
        attributes += `${key}="${attributesObject[key]}" `
    })

    const restoreLabel = isNil(window.NeosCMS)
        ? window.NeosCMS.I18n.translate(
            'impersonate.label.restoreUserButton',
            'Back to user "{0}"',
            'Neos.Neos',
            'Main',
            user.accountIdentifier
        )
        : `Restore user "${user.accountIdentifier}"`
    return `<button ${attributes}>${impersonateIcon} ${restoreLabel}</button>`
}

export { RestoreButton }
