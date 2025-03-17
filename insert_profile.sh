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

# 4. Password Validation Function
validate_password() {
  local pass="$1"
  local pass_len=${#pass}

  # Check password length
  if [ "$pass_len" -lt 8 ]; then
    echo "Password must be at least 8 characters long."
    return 1
  fi

  # Check for at least one uppercase letter
  if ! echo "$pass" | grep -qP '[A-Z]'; then
    echo "Password must contain at least one uppercase letter."
    return 1
  fi

  # Check for at least one lowercase letter
  if ! echo "$pass" | grep -qP '[a-z]'; then
    echo "Password must contain at least one lowercase letter."
    return 1
  fi

  # Check for at least one digit
  if ! echo "$pass" | grep -qP '[0-9]'; then
    echo "Password must contain at least one digit."
    return 1
  fi

  # Check for at least one special character from @#$%^&*
  if ! echo "$pass" | grep -qP '[@#$%^&*]'; then
    echo "Password must contain at least one special character (@#$%^&*)."
    return 1
  fi

  return 0
}

# 5. Validate the provided password
validate_password "$password"
if [ $? -ne 0 ]; then
  exit 1
fi

# 6. Generate bcrypt hash using htpasswd with a cost factor of 12
hashed_password=$(htpasswd -nbBC 12 "" "$password" | tr -d ':\n')

# 7. Database connection details
DB_HOST="localhost"        # Host machine's address
DB_PORT="543"             # PostgreSQL port
DB_NAME="netsec"           # Database name
DB_USER="postgres"         # Database username
DB_PASSWORD="postgres"     # Database password

# 8. Construct the SQL command
SQL_COMMAND="INSERT INTO Profile (Username, Email, PasswordHash) VALUES ('$username', '$email', '$hashed_password');"

# 9. Execute the SQL command using psql
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "$SQL_COMMAND"

# 10. Check the result and handle success or error
if [ $? -eq 0 ]; then
  echo "Profile for '$username' inserted successfully."
else
  echo "Failed to insert profile for '$username'."
  echo "Please ensure the PostgreSQL container is running and that the database credentials are correct."
fi
