#!/bin/bash

source_dir="/home/mike/it490/frontend"

destination_dir="/var/www/html"

rsync -av "$source_dir/" "$destination_dir/"

if [ $? -eq 0 ]; then
    echo "Files copied successfully."
else
    echo "Error: Failed to copy files."
fi
