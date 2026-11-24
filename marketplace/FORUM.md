# OpenPlex — Blesta forum thread

The thread for the Blesta Community Forums' "The Marketplace" section
(https://www.blesta.com/forums/forum/28-the-marketplace/), posted as the forum user
"OpenPlex". The forum's rules: one thread per extension, named after it, started by its
author. Once posted, put the thread's URL in the listing's "Discussion URL"
(marketplace.blesta.com → Manage → Edit → Update Extension) and in LISTING.md.

| Field | Value |
|---|---|
| Title | OpenPlex module: provision OpenPlex hosting accounts from Blesta (free, open source) |
| Tags | module, openplex, hosting, control panel, provisioning |

## Post

Hi everyone,

OpenPlex is a free, open-source web hosting control panel. This module lets you sell OpenPlex hosting from Blesta: accounts are created, suspended and removed as orders are paid, go overdue or are cancelled.

**What it does**

- **Add service:** a customer or reseller account with the package's limits, and a website for the order's domain.
- **Suspend / unsuspend:** the account can't sign in and its websites go offline; unsuspending brings everything back.
- **Cancel:** deletes the websites (files, mail, DNS), the databases and the account.
- **Change package** and **change password**.
- **Single sign-on:** customers, and admins from the service page, open the panel from the client area with a one-time link.

**Package options:** account type, number of websites and databases, database size, whether to create the website and with which runtime (PHP, Node.js, Python, Go or static), and the panel's language.

**Requirements:** Blesta 5 or 6, PHP 7.4+ with cURL, OpenPlex 0.31 or newer. Tested end to end on Blesta 6.0.5 with OpenPlex 0.34.0.

- Download: https://marketplace.blesta.com/extension/464
- Source and install guide: https://github.com/avakiam/openplex-blesta
- OpenPlex: https://openplex.dev

Free and MIT-licensed. Questions, bug reports and feature requests are welcome here or on GitHub issues.
