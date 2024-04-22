#!/bin/bash

DB_NAME="deploymentDB"
DB_USER="new"
DB_PASS="MikeNuhaJames123!"
DB_HOST="localhost"
PROCESSED_DIR="/home/mike/Desktop/processed/"

function get_qa_dest_dir() {
    local package_name=$1
    case "$package_name" in
        "frontend_files")
            echo "mike@192.168.192.185:/home/mike/"
            ;;
        "rmq_files")
            echo "mike@192.168.192.232:/home/mike/"
            ;;
        "dmz_files")
            echo "mike@192.168.192.52:/home/mike/"
            ;;
    esac
}

while true; do
    QUERY="SELECT package_name, version FROM packages WHERE status='new' ORDER BY creation_date ASC;"

    while read -r PACKAGE_NAME PACKAGE_VERSION; do
        if [[ -n "$PACKAGE_NAME" && -n "$PACKAGE_VERSION" ]]; then
            TAR_FILE="${PROCESSED_DIR}${PACKAGE_NAME}_v${PACKAGE_VERSION}.tar.gz"
            QA_DEST_DIR=$(get_qa_dest_dir "$PACKAGE_NAME")

            if [ -f "$TAR_FILE" ]; then
                echo "Transferring $(basename "$TAR_FILE") to QA at $QA_DEST_DIR..."

                if scp "$TAR_FILE" "$QA_DEST_DIR"; then
                    echo "Transfer successful"
                    UPDATE_QUERY="UPDATE packages SET status='in qa' WHERE package_name='${PACKAGE_NAME}' AND version=${PACKAGE_VERSION};"
                    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$UPDATE_QUERY"
                else
                    echo "Failed to transfer $(basename "$TAR_FILE")."
                fi
            else
                echo "File $TAR_FILE does not exist."
            fi
        fi
    done < <(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e "$QUERY")

    echo "Waiting for new packages..."
    sleep 6
done
