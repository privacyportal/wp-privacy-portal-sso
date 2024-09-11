#!/usr/bin/env bash

set -eu

# Activate the plugin.
cd "/app"
echo "Activating plugin..."
if ! wp plugin is-active privacy-portal-sso 2>/dev/null; then
	wp plugin activate privacy-portal-sso --quiet
fi

echo "Done!"
