# KDISC Master Data Portal

A secure PHP/MySQL application that exposes public master data (districts, local bodies, institutions, etc.) with Bootstrap 5 layouts plus an authenticated administrator area for maintaining records.

## Setup

1. Copy `.env.example` to `.env` and set your MySQL credentials and app secret.
2. Create the database and tables:
   ```sh
   mysql -u <user> -p -e "CREATE DATABASE IF NOT EXISTS data_kdiscmis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u <user> -p data_kdiscmis < database/schema.sql
   ```
3. Serve the project (e.g., via `php -S 0.0.0.0:8000 -t public`).
4. Login with the seeded admin account (`admin` / `password123`) and create additional users from the Admin page.

## Features

- Responsive Bootstrap 5 UI with navbar and card-based layouts for desktop, tablet, and mobile.
- Public catalogue pages with search plus cascading filters for every master list.
- Secure admin workflows with CSRF protection, password hashing, and role-based access to manage data and users.
- Master cards on the home page show real-time counts and link into detail pages with card-based tables.

## Masters included

- Districts
- Local bodies (corporations, municipalities, grama/block/district panchayats)
- Job stations (district/block-wise)
- Academic institutions (district, category, type)
- Education courses/trades (district, category)
- CDS (district, local body type)
- ADS (district, local body type, local body)

## Notes

- The schema seeds an `admin` user with password `password123` (update after first login).
- All SQL statements use prepared queries to avoid injection, and sessions are required for administration.
