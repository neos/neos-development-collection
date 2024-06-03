import { isNil } from '../Helper'
import {ApiService} from '../Services'
import { RestoreButton } from '../Templates/RestoreButton'

export default class UserMenu {
    constructor(_root) {
        const userMenuElement = document.querySelector('.neos-user-menu[data-csrf-token]')
        this._csrfToken = isNil(userMenuElement) ? '' : userMenuElement.getAttribute('data-csrf-token')
        this._baseModuleUri = this._getBaseModuleUri(userMenuElement)
        this._basePath = this._getImpersonationBasePath()
        this._root = _root
        this._apiService = new ApiService(this._basePath, this._csrfToken)

        if (!isNil(_root)) {
            this._checkImpersonateStatus()
        }
    }

    _getBaseModuleUri(element) {
        let url = '/neos'
        if (!isNil(element) && element.hasAttribute('data-module-basepath')) {
            url = element.getAttribute('data-module-basepath')
        }
        return url
    }

    _getImpersonationBasePath() {
        let basePath = this._baseModuleUri
        if (!basePath.endsWith('/')) {
            basePath += '/'
        }
        return basePath + 'impersonate/'
    }
    _renderRestoreButton(user) {
        const userMenuDropDown = this._root.querySelector('.neos-dropdown-menu')
        if (isNil(userMenuDropDown) || isNil(user)) {
            return false
        }

        // append restore button to the user menu
        const restoreListItem = document.createElement('li')
        restoreListItem.innerHTML = RestoreButton(user)
        userMenuDropDown.appendChild(restoreListItem)

        // add event listener for restore api call
        const restoreButtonElement = userMenuDropDown.querySelector(
            '.restore-user'
        )
        if (!isNil(restoreButtonElement)) {
            restoreButtonElement.addEventListener(
                'click',
                this._restoreUser.bind(this)
            )
        }
    }

    _checkImpersonateStatus() {
        const response = this._apiService.callStatus()
        response
            .then((data) => {
                const { origin, status } = data
                if (status && !isNil(origin)) {
                    this._renderRestoreButton(origin)
                }
            })
            .catch(function (error) {
                // error occured but we just don`t render the restore button
            })
    }

    _restoreUser(event) {
        event.preventDefault()
        const button = event.currentTarget
        if (isNil(button)) {
            return false
        }

        const response = this._apiService.callRestore()
        response
            .then((data) => {
                const { origin, impersonate } = data
                const message = window.NeosCMS.I18n.translate(
                    'impersonate.success.restoreUser',
                    'Switched back from {0} to the orginal user {1}.',
                    'Neos.Neos',
                    'Main',
                    {
                        0: impersonate.accountIdentifier,
                        1: origin.accountIdentifier,
                    }
                )
                window.NeosCMS.Notification.ok(message)

                // load default backend, so we don't need to care for the module permissions.
                // because in not every neos version the users have by default the content module or user module
                window.location.href = this._baseModuleUri
            })
            .catch(function (error) {
                if (window.NeosCMS) {
                    const message = window.NeosCMS.I18n.translate(
                        'impersonate.error.restoreUser',
                        'Could not switch back to the original user.',
                        'Neos.Neos'
                    )
                    window.NeosCMS.Notification.error(message)
                }
            })
    }
}
