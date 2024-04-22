#!/bin/bash

TARBALL_DIR="/home/mike/"
DMZ_TARGET_DIR="/home/mike/it490/DMZ/"
CHECK_INTERVAL=10

while true; do
    TARBALL=$(ls -t "${TARBALL_DIR}"dmz_files*.tar.gz | head -n 1)

    if [ -f "$TARBALL" ]; then
        echo "Found tarball: $(basename "$TARBALL")"
        TEMP_DIR=$(mktemp -d)

        if [ ! -d "$TEMP_DIR" ]; then
            echo "Failed to create a temporary directory"
            exit 1
        fi

        echo "Unpacking tarball..."
        tar -xzvf "$TARBALL" -C "$TEMP_DIR"

        echo "Updating DMZ directory..."
        sudo rsync -av --delete "${TEMP_DIR}/" "$DMZ_TARGET_DIR"

        rm -rf "$TEMP_DIR"
        rm "$TARBALL"
        echo "Files have been unpacked and synchronized to the DMZ directory."

    else
        echo "No tarball found in $TARBALL_DIR"
    fi

    sleep $CHECK_INTERVAL
done
