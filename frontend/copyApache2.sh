#!/bin/bash

# Source directory
source_dir="/home/mike/it490/frontend"

# Destination directory
destination_dir="/var/www/html"

# Copy files from source directory to destination directory
cp -r "$source_dir"/* "$destination_dir"

# Check if the copy operation was successful
if [ $? -eq 0 ]; then
    echo "Files copied successfully."
else
    echo "Error: Failed to copy files."
fi
