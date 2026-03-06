#!/bin/bash

# Check if a backup file is provided
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <backup_file.sql>"
    exit 1
fi

BACKUP_FILE=$1

# Check if the backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file $BACKUP_FILE does not exist."
    exit 1
fi

# Restore the database
docker exec -i mysql-container-name mysql -u root -p'your_password' your_database_name < "$BACKUP_FILE"

echo "Database restored from $BACKUP_FILE."