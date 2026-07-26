#!/usr/bin/env bash
#
# Container entrypoint for the Origin Driving School Management System.
#   1. Configures Apache to listen on the platform-provided $PORT.
#   2. Waits for the MySQL database to become reachable.
#   3. Seeds the schema + sample data on first boot (idempotent).
#   4. Starts Apache in the foreground.
#
set -e

# ----------------------------------------------------------------------------
# 1. Apache listen port
# ----------------------------------------------------------------------------
: "${PORT:=80}"
echo "Listen ${PORT}" > /etc/apache2/ports.conf
# Silence the "could not determine server's fully qualified domain name" notice.
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null 2>&1 || true

# ----------------------------------------------------------------------------
# 2. Resolve database connection details
#    (Railway's MySQL plugin exposes MYSQL* variables; fall back to DB_*.)
# ----------------------------------------------------------------------------
DB_HOST="${MYSQLHOST:-${DB_HOST:-}}"
DB_PORT="${MYSQLPORT:-${DB_PORT:-3306}}"
DB_USER="${MYSQLUSER:-${DB_USER:-root}}"
DB_NAME="${MYSQLDATABASE:-${DB_NAME:-origin_driving_school}}"
# mysql/mysqladmin read the password from MYSQL_PWD, avoiding quoting issues.
export MYSQL_PWD="${MYSQLPASSWORD:-${DB_PASS:-}}"

SQL_FILE="/var/www/html/origin_driving_school/origin_driving_school_database.sql"

if [ -n "$DB_HOST" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
    for i in $(seq 1 60); do
        if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --silent >/dev/null 2>&1; then
            echo "MySQL is reachable."
            break
        fi
        sleep 2
    done

    # ------------------------------------------------------------------------
    # 3. Seed the database if the core tables are not present yet.
    # ------------------------------------------------------------------------
    TABLE_EXISTS="$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -N -B \
        -e "SELECT COUNT(*) FROM information_schema.tables \
            WHERE table_schema='${DB_NAME}' AND table_name='users';" 2>/dev/null || echo 0)"

    if [ "${TABLE_EXISTS:-0}" = "0" ]; then
        echo "Seeding database '${DB_NAME}' ..."
        # Remove the DROP/CREATE DATABASE and USE statements so the dump loads
        # into the platform-provided database, then import it.
        if sed -E '/DROP DATABASE/d; /CREATE DATABASE/d; /^[[:space:]]*USE[[:space:]]/d' "$SQL_FILE" \
            | mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$DB_NAME"; then
            echo "Database seeded successfully."
        else
            echo "WARNING: database seeding failed. Check the MySQL credentials/permissions."
        fi
    else
        echo "Database already contains the 'users' table; skipping seed."
    fi
else
    echo "No database host configured (MYSQLHOST/DB_HOST); skipping DB seeding."
fi

# ----------------------------------------------------------------------------
# 4. Hand off to Apache.
# ----------------------------------------------------------------------------
echo "Starting Apache on port ${PORT} ..."
exec apache2-foreground
