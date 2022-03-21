import {isNil} from '../../Helper'
import {ApiService} from '../../Services'
import {ImpersonateButton} from '../../Templates/ImpersonateButton'

const BASE_PATH = '/neos/impersonate/'
export default class UserManagement {
    constructor(_root) {
        const csfrTokenField = document.querySelector('[data-csrf-token]')
        this._root = _root
        this._csrfToken = !isNil(csfrTokenField) ? csfrTokenField.getAttribute('data-csrf-token') : ''
        console.log(this._csrfToken);
        this._apiService = new ApiService(BASE_PATH, this._csrfToken)

        if (!isNil(_root)) {
            this._initialize()
        }
    }

    _initialize() {
        this._renderImpersonateButtons()
        this._setupEventListeners()
    }

    _setupEventListeners() {
        const impersonateButtons = this._root.querySelectorAll('button.impersonate-user')
        impersonateButtons.forEach(_impersonateButton => {
            _impersonateButton.addEventListener('click', this._impersonateUser.bind(this));
        });
    }

    _renderImpersonateButtons() {
        const userTableActionButtons = Array.from(this._root.querySelectorAll('.neos-table .neos-action'))
        userTableActionButtons.forEach(_actionContainer => {
            const deleteButton = _actionContainer.querySelector('button.neos-button-danger')
            const showButton = _actionContainer.querySelector('a[href*="show"]')
            if (isNil(showButton)) {
                return false
            }

            const showButtonUri = new URL(decodeURIComponent(showButton.getAttribute('href')))
            const showButtonUriParameter = new URLSearchParams(showButtonUri.search)

            // user information from DOM
            const userIdentifier = showButtonUriParameter.get('moduleArguments[user][__identity]')
            const isCurrentUser = !isNil(deleteButton) && deleteButton.classList.contains('neos-disabled')

            const impersonateButtonMarkup = ImpersonateButton(userIdentifier, isCurrentUser)
            showButton.parentElement.innerHTML += impersonateButtonMarkup
        })
    }

    _impersonateUser(event) {
        event.preventDefault();
        const button = event.currentTarget;
        if (isNil(button)) {
            return false
        }

        const identifier = button.getAttribute('data-user-identifier')
        const response = this._apiService.callUserChange(identifier);
        response
            .then((data) => {
                const {user, status} = data
                const username = isNil(user) ? '' : user.accountIdentifier
                const message = window.NeosCMS.I18n.translate('success.impersonateUser', 'Switched to the new user {0}.', 'Neos.Neos', 'Main', {0: username})
                window.NeosCMS.Notification.ok(message)

                // load default backend, so we don't need to care for the module permissions.
                // because in not every neos version the users have by default the content module or user module
                window.location.pathname = '/neos'
            })
            .catch(function (error) {
                if (window.NeosCMS) {
                    const message = window.NeosCMS.I18n.translate('error.impersonateUser', 'Could not switch to the requested user.', 'Neos.Neos')
                    window.NeosCMS.Notification.error(message)
                }
            });
    }
}
