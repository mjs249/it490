#!/bin/bash

SOURCE_DIR1="/home/mike/it490/rabbitmq"
SOURCE_DIR2="/home/mike/it490/DB"
DEPLOYMENT_SERVER="mike@192.168.192.188"
TARGET_DIR="/home/mike/Desktop/"
TARBALL_NAME="rmq_files_$(date +%Y-%m-%d_%H-%M-%S).tar.gz"
FULL_TARBALL_PATH="${TARGET_DIR}${TARBALL_NAME}"

echo "Creating a single tarball of both RMQ and DB directories..."
tar -czvf "$FULL_TARBALL_PATH" -C "/home/mike/it490" "rabbitmq" "DB"

echo "Transferring the tarball to the deployment server..."
scp "$FULL_TARBALL_PATH" "${DEPLOYMENT_SERVER}:${TARGET_DIR}"

echo "Deployment package transferred successfully."
