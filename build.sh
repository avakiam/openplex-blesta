#!/bin/sh
# Builds openplex-blesta-<version>.zip with components/modules/openplex,
# ready to unzip in a Blesta installation's folder.
set -eu
cd "$(dirname "$0")"
version=$(sed -n 's/.*"version": *"\([^"]*\)".*/\1/p' components/modules/openplex/config.json)
out="openplex-blesta-$version.zip"
rm -f "$out"
cp LICENSE README.md components/modules/openplex/
(zip -qr "$out" components/modules/openplex)
rm components/modules/openplex/LICENSE components/modules/openplex/README.md
echo "$out"
