# UniStay – University Boarding Finder

## Project layout

This repository currently contains the project scaffold. The PHP application
pages and database scripts have not been added yet.

Use these locations when adding the PHP application:

```text
index.php, listings.php, listing.php, inquiry.php
login.php, register.php, logout.php
owner/                 Owner pages
admin/                 Administrator pages
includes/              Shared PHP includes and database connection
assets/css/            Stylesheets
assets/js/             JavaScript
assets/images/         Project images
uploads/listings/      Uploaded listing photos
database/              Database schema and seed scripts
```

The existing `backend/`, `frontend/`, `mobile/`, `docs/`, and database
subdirectories are preserved for the work already planned by the team. Keep
new files in the PHP locations above unless the team agrees to a different
layout.

## Run with XAMPP

1. Copy or clone this repository into XAMPP's `htdocs` directory (for example,
   `C:\xampp\htdocs\UNISTAY` on Windows).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open `http://localhost/UNISTAY/` in a browser.
4. Once a database schema is added under `database/`, create the project
   database in phpMyAdmin and import the applicable SQL script. Configure
   connection settings in the shared PHP includes as the application is
   implemented.

There are currently no PHP entry pages or SQL scripts in the repository, so
the application and database cannot yet be run. Add those files before using
the steps above to test UniStay functionality.
