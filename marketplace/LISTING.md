# OpenPlex — Blesta Marketplace listing

The texts for https://marketplace.blesta.com (sign in with the Avakiam account on
account.blesta.com). Keep them in step with README.md and CHANGES.md.

## Author (first sign-in only)

| Field | Value |
|---|---|
| Author Name | OpenPlex |
| Website URL | https://openplex.dev |

## New Extension

| Field | Value |
|---|---|
| Category | Modules |
| Type | Free |
| Logo | `logo-512.png` (or `logo-256.png` if a smaller one is asked for) |
| Name | OpenPlex |
| Tags | openplex, hosting, control panel, provisioning, single sign-on, open source, docker, wordpress |
| Discussion URL | *(empty for now; a thread on the Blesta forums' Marketplace section can be added later)* |

### Description (markdown)

```markdown
Sell web hosting on **[OpenPlex](https://openplex.dev)**, the open-source hosting control panel, straight from Blesta. Blesta creates, suspends and removes the OpenPlex accounts as orders are paid, go overdue or are cancelled, and your customers open their control panel from the client area without typing a password.

**What it does**

| Blesta action | In OpenPlex |
|---|---|
| Add service | A customer (or reseller) account with the package's limits, and a website for the order's domain |
| Suspend | The account can't sign in and its websites go offline (a reseller's customers' too) |
| Unsuspend | The account and its websites are back as they were |
| Cancel | The websites (files, mail, DNS), the databases and the account are deleted |
| Change package | The new limits and account type |
| Change password | The account's new password |
| Log in to OpenPlex | The customer, or an admin from the service page, is signed in to the panel with a one-time link |

**Package settings:** account type (customer or reseller), number of websites and databases, database size, whether to create the website and with which runtime (PHP, Node.js, Python, Go or static), and the panel's language (English, Spanish, Catalan or the customer's browser).

**Details**

- The API key is checked when you add the server and stored encrypted; customer keys are refused.
- Usernames are derived from the domain (or set by you) and passwords generated when none is given; both can go in the welcome e-mail.
- Every API call goes to Blesta's module log, without the API key, passwords or sign-in links.
- English and Spanish.

**Requirements:** OpenPlex 0.31 or newer, Blesta 5 or 6, PHP 7.4+ with cURL. Tested end to end on Blesta 6.0.5 with OpenPlex 0.34.0.

**Install:** unzip the release in your Blesta folder, install **OpenPlex** under Settings → Company → Modules, add your panel as a server with an administrator's (or a reseller's) API key, and create a package. Full guide: https://github.com/avakiam/openplex-blesta

Free and open source (MIT). OpenPlex itself is free software (AGPL-3.0): https://github.com/avakiam/openplex
```

## Version

| Field | Value |
|---|---|
| Version | 1.0.1 |
| Download URL | https://github.com/avakiam/openplex-blesta/releases/download/v1.0.1/openplex-blesta-1.0.1.zip |

### Changelog

```text
1.0.1
- Deleting a server or a server group from the module's page works in Blesta 6 (it asks for confirmation with the browser's own dialog, in Blesta 5 and 6).
- Tested end to end in a real Blesta 6.0.5 against OpenPlex 0.34.0: add, suspend, unsuspend, change package, change password, single sign-on, cancel.

1.0.0
- First version: servers checked by their API key; packages with account type, limits, website and dashboard language; add, suspend, unsuspend, cancel and change package; password changes; single sign-on for customers and admins; English and Spanish.
```

## Screenshots (in this order)

1. `screenshots/1-server.png` — Adding the OpenPlex panel as a server: domain, HTTPS and API key.
2. `screenshots/2-package.png` — Package options: account type, limits, website runtime and language.
3. `screenshots/3-admin-service.png` — A provisioned service in the admin area, with the "Log in to OpenPlex" tab.
4. `screenshots/4-client-area.png` — The client area: one click opens the control panel, already signed in.
5. `screenshots/5-openplex.png` — The customer's OpenPlex dashboard after single sign-on.

Taken on a local Blesta 6.0.5 (development licence) with demo data: client "Jane
Smith", packages "Starter" and "Business", server "OpenPlex EU-1".
