#!/bin/bash

SOURCE_DIR="/home/mike/it490/DMZ/"
TARGET_DIR="/home/mike/Desktop/"
DEPLOYMENT_SERVER="mike@192.168.192.188"
TARBALL_NAME="dmz_files_$(date +%Y-%m-%d_%H-%M-%S).tar.gz"

echo "Creating tarball of the DMZ files..."
tar -czvf "$TARBALL_NAME" -C "$SOURCE_DIR" .

echo "Transferring tarball to the deployment server..."
scp "$TARBALL_NAME" "$DEPLOYMENT_SERVER":"$TARGET_DIR"

echo "Deployment package transferred successfully."
