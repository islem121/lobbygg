#!/bin/bash

# Define variables
DB_NAME="your_database_name"
DB_USER="your_database_user"
DB_PASSWORD="your_database_password"
BACKUP_DIR="./backups"
TIMESTAMP=$(date +"%Y%m%d%H%M")

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Create a backup
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/${DB_NAME}_backup_$TIMESTAMP.sql

# Check if the backup was successful
if [ $? -eq 0 ]; then
  echo "Backup of database '$DB_NAME' created successfully at $BACKUP_DIR/${DB_NAME}_backup_$TIMESTAMP.sql"
else
  echo "Error occurred during backup of database '$DB_NAME'"
fi