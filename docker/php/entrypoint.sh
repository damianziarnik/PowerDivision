#!/bin/sh
set -e

# Ensure /dev/stdout exists as a symlink to the current process stdout.
# Some Docker base images (including php:8.3-apache) do not create it.
if [ ! -e /dev/stdout ]; then
    ln -sf /proc/self/fd/1 /dev/stdout
fi

# Ensure Apache workers (www-data) can write to the descriptor.
chmod 666 /dev/stdout 2>/dev/null || true

exec "$@"

