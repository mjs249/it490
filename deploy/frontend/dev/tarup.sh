#!/bin/bash

SOURCE_DIR="/var/www/html/"
TARGET_DIR="/home/mike/Desktop/"
DEPLOYMENT_SERVER="mike@192.168.192.188"
TARBALL_NAME="frontend_files_$(date +%Y-%m-%d_%H-%M-%S).tar.gz"

echo "Creating tarball of the Apache front-end files..."
tar -czvf "$TARBALL_NAME" -C "$SOURCE_DIR" .

echo "Transferring tarball to the deployment server..."
scp "$TARBALL_NAME" "$DEPLOYMENT_SERVER":"$TARGET_DIR"

echo "Deployment package transferred successfully."
