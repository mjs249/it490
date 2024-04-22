#!/bin/bash

TARBALL_DIR="/home/mike/"
TARGET_DIR="/var/www/html/"
TARBALL=$(ls -t ${TARBALL_DIR}frontend_files*.tar.gz | head -n 1)

if [ -f "$TARBALL" ]; then
    echo "Found tarball: $(basename "$TARBALL")"
    TEMP_DIR=$(mktemp -d)

    if [ ! -d "$TEMP_DIR" ]; then
        echo "Failed to create a temporary directory"
        exit 1
    fi

    tar -xzvf "$TARBALL" -C "$TEMP_DIR"
    cp -R ${TEMP_DIR}/* "$TARGET_DIR"
    rm -rf "$TEMP_DIR"
    echo "Files have been unpacked to $TARGET_DIR"
else
    echo "No tarball found in $TARBALL_DIR"
fi
