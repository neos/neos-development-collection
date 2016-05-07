============================
Adding A Simple Contact Form
============================

Using the TYPO3.Form package you can easily create and adopt simple to very complex forms.
For it to work properly you just have to define where it should find its form configurations.

Yaml (Sites/Vendor.Site/Configuration/Settings.yaml) ::

  TYPO3:
    Form:
      yamlPersistenceManager:
        savePath: 'resource://Vendor.Site/Private/Form/'

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

  'TYPO3.Neos.NodeTypes:Form':
    properties:
      formIdentifier:
        ui:
          inspector:
            editorOptions:
              values:
                '': ~
                # Maps to the file Sites/Vendor.Site/Resources/Private/Form/contact-form.yaml
                'contact-form':
                  label: 'contact-form'

Now place a valid TYPO3.Form Yaml configuration the Private/Form folder. Then add a Form Element where
you wish the form to be displayed and select it from the dropdown in the Inspector.

Yaml (Sites/Vendor.Site/Resources/Private/Form/contact-form.yaml) ::

  type: 'TYPO3.Form:Form'
  identifier: contact-form
  label: Contact
  renderingOptions:
    submitButtonLabel: Send
  renderables:
    -
      type: 'TYPO3.Form:Page'
      identifier: page-one
      label: Contact
      renderables:
        -
          type: 'TYPO3.Form:SingleLineText'
          identifier: name
          label: Name
          validators:
            - identifier: 'TYPO3.Flow:NotEmpty'
          properties:
            placeholder: Name
          defaultValue: ''
        -
          type: 'TYPO3.Form:SingleLineText'
          identifier: email
          label: E-Mail
          validators:
            - identifier: 'TYPO3.Flow:NotEmpty'
            - identifier: 'TYPO3.Flow:EmailAddress'
          properties:
            placeholder: 'E-Mail'
          defaultValue: ''
        -
          type: 'TYPO3.Form:MultiLineText'
          identifier: message
          label: Message
          validators:
            - identifier: 'TYPO3.Flow:NotEmpty'
          properties:
            placeholder: 'Your Message'
          defaultValue: ''
  finishers:
    -
      identifier: 'TYPO3.Form:Email'
      options:
        templatePathAndFilename: resource://Vendor.Site/Private/Templates/Email/Message.txt
        subject: Contact from example.net
        recipientAddress: office@example.net
        recipientName: 'Office of Company'
        senderAddress: server@example.net
        senderName: Server example.net
        replyToAddress: office@example.net
        format: plaintext
    -
      identifier: 'TYPO3.Form:Confirmation'
      options:
        message: >
          <h3>Thank you for your feedback</h3>
          <p>We will process it as soon as possible.</p>

In this example we are using the TYPO3.Form:Email Finisher.
The Email Finisher requires the TYPO3.SwiftMailer package to be installed.
It sends an E-Mail using the defined template and settings.
By the second Finisher a confirmation is displayed.

Html (Sites/Vendor.Site/Resources/Private/Templates/Email/Message.txt) ::

  Hello,

  <f:for each="{form.formState.formValues}" as="value" key="label">
    {label}: {value}
  </f:for>

  Thanks

To find out more about how to create forms see the TYPO3.Form package. There is even a Click Form Builder that
exports the Yaml settings files.

.. warning:: Make sure the Neos.Demo package (or other) is deactivated. Otherwise the setting ``TYPO3.Form.yamlPersistenceManager.savePath`` may be overwritten by another package. You can deactivate a package with the command ``./flow package:deactivate <PackageKey>``.
