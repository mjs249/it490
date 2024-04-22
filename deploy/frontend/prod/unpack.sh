#!/bin/bash

TARBALL_DIR="/home/mike/"
TARGET_DIR="/var/www/html/"
CHECK_INTERVAL=10

while true; do
    TARBALL=$(ls -t "${TARBALL_DIR}"frontend_files*.tar.gz | head -n 1)

    if [ -f "$TARBALL" ]; then
        echo "Found tarball: $(basename "$TARBALL")"
        TEMP_DIR=$(mktemp -d)

        if [ ! -d "$TEMP_DIR" ]; then
            echo "Failed to create a temporary directory"
            exit 1
        fi

        tar -xzvf "$TARBALL" -C "$TEMP_DIR"
        sudo rsync -av --delete "${TEMP_DIR}/" "$TARGET_DIR"
        rm -rf "$TEMP_DIR"
        rm "$TARBALL"
        echo "Files have been unpacked and synchronized to $TARGET_DIR"

        echo "Reloading Apache2 to apply changes..."
        sudo systemctl reload apache2
        echo "Apache2 reloaded successfully."

    else
        echo "No tarball found in $TARBALL_DIR"
    fi

    sleep $CHECK_INTERVAL
done
