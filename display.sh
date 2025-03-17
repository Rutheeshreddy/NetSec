#!/bin/bash
# Script: fetch_profiles.sh
# Description: Connect to a PostgreSQL database running in a Docker container and retrieve all entries from the 'Profile' table.

# 1. Prerequisite Check: Ensure psql is installed
if ! command -v psql > /dev/null; then
  echo "Error: psql (PostgreSQL client) is not installed."
  echo "Please install psql (e.g., via your package manager) and try again."
  exit 1
fi

# 2. Database connection details
DB_HOST="localhost"        # Host machine's address
DB_PORT="543"             # PostgreSQL port
DB_NAME="netsec"           # Database name
DB_USER="postgres"         # Database username
DB_PASSWORD="postgres"     # Database password

# 3. Construct the SQL command
SQL_COMMAND="SELECT * FROM Profile;"

# 4. Execute the SQL command using psql
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "$SQL_COMMAND"

# 5. Check the result and handle success or error
if [ $? -eq 0 ]; then
  echo "Profiles retrieved successfully."
else
  echo "Failed to retrieve profiles."
  echo "Please ensure the PostgreSQL container is running and that the database credentials are correct."
fi
