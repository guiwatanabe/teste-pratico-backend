#!/bin/bash
set -e

# Install composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# If no command provided, open a shell
if [ $# -eq 0 ]; then
    exec bash
fi

# If a single string argument is passed (e.g. "php artisan serve ..."),
# run it through the shell so it is parsed into program + args.
if [ $# -eq 1 ]; then
    exec sh -c "$1"
fi

# Otherwise exec the provided args directly
exec "$@"
