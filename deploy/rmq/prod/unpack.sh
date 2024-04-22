#!/bin/bash

TARBALL_DIR="/home/mike/"
RABBITMQ_TARGET_DIR="/home/mike/it490/rabbitmq"
DB_TARGET_DIR="/home/mike/it490/DB/"
CHECK_INTERVAL=10

while true; do
    TARBALL=$(ls -t "${TARBALL_DIR}"rmq_files*.tar.gz | head -n 1)

    if [ -f "$TARBALL" ]; then
        echo "Found tarball: $(basename "$TARBALL")"
        TEMP_DIR=$(mktemp -d)

        if [ ! -d "$TEMP_DIR" ]; then
            echo "Failed to create a temporary directory"
            exit 1
        fi

        echo "Unpacking tarball..."
        tar -xzvf "$TARBALL" -C "$TEMP_DIR"

        if [ -d "${TEMP_DIR}/rabbitmq" ]; then
            echo "Updating RabbitMQ directory..."
            sudo rsync -av --delete "${TEMP_DIR}/rabbitmq/" "$RABBITMQ_TARGET_DIR"
        fi

        if [ -d "${TEMP_DIR}/DB" ]; then
            echo "Updating DB directory..."
            sudo rsync -av --delete "${TEMP_DIR}/DB/" "$DB_TARGET_DIR"
        fi

        rm -rf "$TEMP_DIR"
        rm "$TARBALL"
        echo "Files have been unpacked and synchronized to their respective directories."

    else
        echo "No tarball found in $TARBALL_DIR"
    fi

    sleep $CHECK_INTERVAL
done
