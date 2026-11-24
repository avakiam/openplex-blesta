# Changes

## 1.0.1

- Deleting a server or a server group from the module's page works in Blesta 6:
  the page no longer relies on jQuery being loaded before it, and asks for
  confirmation with the browser's own dialog (Blesta 5 and 6).
- Tested end to end in a real Blesta 6.0.5 against OpenPlex 0.34.0
  (`tests/lifecycle-blesta.php`): add, suspend, unsuspend, change package,
  change password, single sign-on from the client area, cancel.

## 1.0.0

- First version: servers checked by their API key; packages with account type,
  limits, website and dashboard language; add, suspend, unsuspend, cancel and
  change package; password changes; single sign-on for customers and admins;
  English and Spanish.
