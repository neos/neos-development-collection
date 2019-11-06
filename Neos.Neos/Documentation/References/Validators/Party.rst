.. _`Party Validator Reference`:

Party Validator Reference
=========================

This reference was automatically generated from code on 2019-11-06


.. _`Party Validator Reference: AimAddressValidator`:

AimAddressValidator
-------------------

Validator for AIM addresses.

Checks if the given value is a valid AIM name.

The AIM name has the following requirements: "It must be
between 3 and 16 alphanumeric characters in length and must
begin with a letter."

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: IcqAddressValidator`:

IcqAddressValidator
-------------------

Validator for ICQ addresses.

Checks if the given value is a valid ICQ UIN address.

The ICQ UIN address has the following requirements: "It must be
9 numeric characters." More information is found on:
http://www.icq.com/support/icq_8/start/authorization/en

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: JabberAddressValidator`:

JabberAddressValidator
----------------------

Validator for Jabber addresses.

Checks if the given value is a valid Jabber name.

The Jabber address has the following structure: "name@jabber.org"
More information is found on:
http://tracker.phpbb.com/browse/PHPBB3-3832

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: MsnAddressValidator`:

MsnAddressValidator
-------------------

Validator for MSN addresses.

Checks if the given value is a valid MSN address.

The MSN address has the following structure:
"name@hotmail.com, name@live.com, name@msn.com, name@outlook.com"

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: SipAddressValidator`:

SipAddressValidator
-------------------

Validator for Sip addresses.

Checks if the given value is a valid Sip name.

The Sip address has the following structure: "sip:+4930432343@isp.com"
More information is found on:
http://wiki.snom.com/Features/Dial_Plan/Regular_Expressions

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: SkypeAddressValidator`:

SkypeAddressValidator
---------------------

Validator for Skype addresses.

Checks if the given value is a valid Skype name.

The Skype website says: "It must be between 6-32 characters, start with
a letter and contain only letters and numbers (no spaces or special
characters)."

Nevertheless dash and underscore are allowed as special characters.
Furthermore, account names can contain a colon if they were auto-created
trough a connected Microsoft or Facebook profile. In this case, the syntax
is as follows:
- live:john.due
- Facebook:john.doe

We added period and minus as additional characters because they are
suggested by Skype during registration.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: UrlAddressValidator`:

UrlAddressValidator
-------------------

Validator for URL addresses.

Checks if the given value is a valid URL.

.. note:: A value of NULL or an empty string ('') is considered valid




.. _`Party Validator Reference: YahooAddressValidator`:

YahooAddressValidator
---------------------

Validator for Yahoo addresses.

Checks if the given value is a valid Yahoo address.

The Yahoo address has the following structure:
"name@yahoo.*"

.. note:: A value of NULL or an empty string ('') is considered valid



