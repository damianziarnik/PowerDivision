#!/bin/sh
set -e

# Ensure /dev/stdout exists as a symlink to the current process stdout.
# With php-fpm catch_workers_output=yes, worker fd 1 is a pipe captured
# by the php-fpm master and forwarded to Docker log collector.
if [ ! -e /dev/stdout ]; then
    ln -sf /proc/self/fd/1 /dev/stdout
fi
chmod 666 /dev/stdout 2>/dev/null || true

exec "$@"
