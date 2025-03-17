#!/bin/bash
# Script: insert_profile.sh
# Description: Connect to a PostgreSQL database running in a Docker container and insert a new profile with a hashed password.

# 1. Prerequisite Check: Ensure psql is installed
if ! command -v psql > /dev/null; then
  echo "Error: psql (PostgreSQL client) is not installed."
  echo "Please install psql (e.g., via your package manager) and try again."
  exit 1
fi

# 2. Check for required arguments
if [ "$#" -ne 3 ]; then
  echo "Usage: $0 <Username> <Email> <Password>"
  exit 1
fi

# 3. Assign arguments to variables
username=$1
email=$2
password=$3

# 4. Hash the password using SHA-256
hashed_password=$(echo -n "$password" | sha256sum | awk '{print $1}')

# 5. Database connection details
DB_HOST="localhost"        # Host machine's address
DB_PORT="543"             # PostgreSQL port
DB_NAME="netsec"           # Database name
DB_USER="postgres"         # Database username
DB_PASSWORD="postgres"     # Database password

# 6. Construct the SQL command
SQL_COMMAND="INSERT INTO Profile (Username, Email, PasswordHash) VALUES ('$username', '$email', '$hashed_password');"

# 7. Execute the SQL command using psql
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "$SQL_COMMAND"

# 8. Check the result and handle success or error
if [ $? -eq 0 ]; then
  echo "Profile for '$username' inserted successfully."
else
  echo "Failed to insert profile for '$username'."
  echo "Please ensure the PostgreSQL container is running and that the database credentials are correct."
fi
