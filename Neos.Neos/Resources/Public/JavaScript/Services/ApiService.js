import { isNil } from '../Helper'

export default class ApiService {
    constructor(_basePath, _csrfToken) {
        if (isNil(_basePath)) {
            let pathError = 'Tried to create API service without a base uri. '
            pathError += 'Please initialize the API service with a base path '
            pathError += 'like "/neos/impersonate/"'
            console.error(pathError)
        }
        this._basePath = _basePath

        if (isNil(_csrfToken)) {
            let csrfError = 'Tried to create API service without a CSFR '
            csrfError += 'token. Please initialize the API service with a token'
            console.error(csrfError)
        }
        this._csrfToken = _csrfToken
    }

    async callUserChange(identifier) {
        const data = {
            user: identifier,
            format: 'json',
        }
        const response = await fetch(this._basePath + 'user-change', {
            method: 'POST',
            credentials: 'include',
            headers: this._getHeader(),
            body: JSON.stringify(data),
        })

        return await response.json()
    }

    async callStatus() {
        const response = await fetch(this._basePath + 'status', {
            method: 'GET',
            credentials: 'include',
            headers: this._getHeader(),
        })

        return await response.json()
    }

    async callRestore() {
        const response = await fetch(this._basePath + 'restore', {
            method: 'POST',
            credentials: 'include',
            headers: this._getHeader(),
        })

        return await response.json()
    }

    _getHeader() {
        return {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Flow-Csrftoken': this._csrfToken,
        }
    }
}
