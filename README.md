# OpenPlex for Blesta

A Blesta provisioning module for [OpenPlex](https://github.com/avakiam/openplex):
sell hosting on an OpenPlex server and let Blesta create, suspend and remove the
accounts as orders are paid, go overdue or are cancelled.

| Blesta action | What happens in OpenPlex |
|---|---|
| Add service | A customer (or reseller) account with the package's limits, and a website for the service's domain |
| Suspend | The account can't sign in and its websites go offline — a reseller's customers' too |
| Unsuspend | The account and each website are back as they were |
| Cancel | The websites (files, mail, DNS) and databases are deleted, then the account |
| Change package | The new limits (websites, databases, database size) and account type |
| Edit service (password) | The account's new password |
| Log in to OpenPlex | The customer (or an admin, from the service page) is signed in to the panel with a one-time link — no password typed |

## Requirements

- OpenPlex **0.31** or newer (the single sign-on, suspension and purge endpoints).
- Blesta 5 or 6 (tested with 6.0.5), PHP 7.4+ with cURL.
- Blesta must reach the panel's API: its HTTPS domain, or its address and port.

## Install

1. Download `openplex-blesta-<version>.zip` from the
   [releases](https://github.com/avakiam/openplex-blesta/releases) and unzip it
   in your Blesta installation's folder, so that you have
   `<blesta>/components/modules/openplex/openplex.php`.
2. In Blesta, **Settings → Company → Modules → Available**, install **OpenPlex**.
3. In OpenPlex, create an API key on **API keys** as an administrator (to sell
   any kind of account) or as a reseller (accounts are then its customers).
4. In Blesta, on the module's page, **Add Server**:
   - **Hostname:** the panel's domain, e.g. `panel.example.com`
   - **Use HTTPS:** on, port 443, when the panel has a domain with HTTPS. For a
     panel reached by its address only: off, port 7080 (keep that port closed to
     the internet except to Blesta).
   - **API key:** the key, `opx_…`. Saving checks that it works and isn't a
     customer's.
5. Create a package (**Packages → Browse → Create Package**) with module
   **OpenPlex**, choose the server or group and the settings below.

## Package settings

| Setting | |
|---|---|
| Account type | Customer, or Reseller (manages customers of its own) |
| Websites | how many websites the account may have; `0` = no limit |
| Databases | how many databases; `0` = no limit |
| Database size (MB) | default size limit of each database; `0` = no limit |
| Create the website | create a website for the service's domain (the domain is then required on the order form) |
| Website runtime / Runtime version | what that website runs: PHP, static, Node.js, Python or Go; empty version = the runtime's default |
| Dashboard language | English, Spanish, Catalan, or the customer's browser |

## Details

- **Username:** OpenPlex accepts 3–32 characters (lowercase letters, digits,
  `_`, `.`, `-`, starting with a letter). Unless the admin gives one, the module
  derives it from the domain and, if it is taken, adds three digits.
- **Password:** at least 10 characters with letters and digits. When none is
  given, the module generates one. Both are stored on the service (the password
  encrypted) and can go in the welcome e-mail (`{service.openplex_username}`,
  `{service.openplex_password}`, `{service.openplex_domain}`).
- **Account ID:** stored on the service as *OpenPlex account ID*. A service added
  without provisioning (an existing account) can be given the ID, or is matched
  to the account with its username **and** the client's e-mail address.
- **Single sign-on:** the *Log in to OpenPlex* button on the service (and its
  tab) opens the panel with a link that lasts two minutes and works once. Only
  active services can sign in.
- **If the website can't be created** (the domain is already on the server, the
  limit is 0…), the account stays and the reason goes to the module log; add the
  website from the panel.
- **Cancelling a reseller** with customers is refused: cancel the customers first.
- **Logs:** every API call goes to Blesta's module log (**Tools → Logs →
  Module**), without the API key, passwords or sign-in links.
- **Languages:** English and Spanish.

## Testing

`tests/lifecycle.php` runs every action the way Blesta calls them, with small
stand-ins for Blesta's classes, against a simulation of the OpenPlex API:

```bash
php -S 127.0.0.1:7999 tests/fake_openplex.php &
php tests/lifecycle.php
```

With `OPX_KEY` (an administrator's API key) and `OPX_PORT`, it runs against a
real panel on the same machine instead.

`tests/lifecycle-blesta.php` runs the same lifecycle inside a real Blesta, through
Blesta's own models, against a real (test) panel; see the comment at its top.

## Licence

MIT — see [LICENSE](LICENSE).
