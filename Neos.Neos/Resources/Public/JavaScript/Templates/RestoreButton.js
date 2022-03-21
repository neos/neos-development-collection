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

    const restoreLabel = isNil(window.Typo3Neos)
        ? window.Typo3Neos.I18n.translate(
            'label.restoreUserButton',
            'Back to user "{0}"',
            'Neos.Neos',
            'Main',
            user.accountIdentifier
        )
        : `Restore user "${user.accountIdentifier}"`
    return `<button ${attributes}>${impersonateIcon} ${restoreLabel}</button>`
}

export { RestoreButton }
