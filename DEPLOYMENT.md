# Deploying to Railway

This project ships as a Docker container (PHP 8.2 + Apache) plus a MySQL
database. On first boot the container waits for MySQL and automatically imports
the schema and sample data, so once the deploy is green you get a fully working
app — login, dashboards, lessons, invoices, and all.

Railway runs the last step under **your** account. It takes about five minutes.

---

## Prerequisites

- A [Railway](https://railway.app) account (sign in with GitHub).
- This repository pushed to GitHub (the `main` branch is used below).

---

## Step 1 — Create the project from GitHub

1. Go to [railway.app](https://railway.app) → **New Project**.
2. Choose **Deploy from GitHub repo**.
3. Select **`Meroidea/Origin_Driving_School_Online_Management_System`**.
4. When asked for the branch, pick **`main`**.

Railway detects the `Dockerfile` and starts building the web service. Let it
build — it will fail to fully start until the database exists (next step).

## Step 2 — Add a MySQL database

1. In the project canvas, click **New → Database → Add MySQL**.
2. Wait until the MySQL service is running.

Railway's MySQL service publishes these variables: `MYSQLHOST`, `MYSQLPORT`,
`MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.

## Step 3 — Give the web service the database variables

Open the **web service** → **Variables** tab → **New Variable**, and add these
five (use the reference syntax so they always match the database):

| Variable | Value |
|----------|-------|
| `MYSQLHOST` | `${{MySQL.MYSQLHOST}}` |
| `MYSQLPORT` | `${{MySQL.MYSQLPORT}}` |
| `MYSQLUSER` | `${{MySQL.MYSQLUSER}}` |
| `MYSQLPASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
| `MYSQLDATABASE` | `${{MySQL.MYSQLDATABASE}}` |

> If your MySQL service is named something other than `MySQL`, use that name in
> the `${{ ... }}` references. Railway auto-completes service names as you type.

*(Optional, for troubleshooting only:* add `APP_DEBUG` = `1` to show PHP errors
on-screen. Remove it once everything works.)*

## Step 4 — Generate the public URL

1. Open the **web service** → **Settings** → **Networking**.
2. Click **Generate Domain**.

Railway gives you a URL like
`https://origin-driving-school-production.up.railway.app`.

## Step 5 — Redeploy and wait

Saving the variables triggers a redeploy. On this boot the container will:

1. Wait for MySQL to be reachable.
2. Detect the empty database and import
   `origin_driving_school_database.sql` (schema + sample data).
3. Start Apache.

Watch the **Deploy Logs**; you should see `Database seeded successfully.` then
`Starting Apache on port ...`.

---

## Step 6 — Log in

Open your generated URL and sign in with any seeded account:

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@origindrivingschool.com.au` | `password` |
| Instructor | `david.smith@origindrivingschool.com.au` | `password` |
| Student | `olivia.taylor@email.com` | `password` |

> **Change these demo passwords** before sharing the link publicly.

---

## How it works (what was added for deployment)

| File | Purpose |
|------|---------|
| `Dockerfile` | Builds the PHP 8.2 + Apache image, installs `pdo_mysql`, points Apache at the `origin_driving_school/` folder. |
| `deploy/000-default.conf` | Apache virtual host (listens on `$PORT`, doc-root = app folder). |
| `deploy/entrypoint.sh` | Sets the port, waits for MySQL, seeds the DB on first boot, starts Apache. |
| `railway.json` | Tells Railway to build from the `Dockerfile`. |
| `.dockerignore` | Keeps `.git`, docs, and the PDF out of the image. |

The application code reads its database credentials and base URL from
environment variables (`config/config.php`), so **no code changes are needed**
between local XAMPP and Railway.

---

## Troubleshooting

- **Build succeeds but the site shows a 5xx / "connection failed":** the web
  service is missing the `MYSQL*` variables from Step 3, or the MySQL service is
  still starting. Confirm the variables, then redeploy.
- **Logs show `database seeding failed`:** the MySQL user lacks privileges on
  the target database, or the credentials are wrong. Re-check the variable
  references. You can also import the SQL manually from the MySQL service's
  **Data** / **Query** tab.
- **Want to see the exact PHP error:** set `APP_DEBUG=1` on the web service,
  redeploy, reproduce, then remove it.
- **Reset the database:** delete and re-add the MySQL service (or drop the
  tables); the next boot re-seeds automatically.

---

## Note on Vercel

Vercel was considered but is **not** suitable for this application: it has no
native PHP runtime, hosts no MySQL database, and its serverless model does not
preserve PHP file-based login sessions or uploaded files between requests.
Railway (used here), Render, or any PHP+MySQL host runs the app as designed.
