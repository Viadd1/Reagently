# Reagently File Overview

## Core files

### `db.php`
Creates the connection to the MySQL database. Other PHP files include this file when they need database access.

### `index.php`
The main homepage of Reagently. It shows the navigation menu, a welcome message, shortcuts to common functions, and recent updates.

### `style.css`
Contains the shared styling for the website, including the header, navigation bar, buttons, homepage sections, and other layout settings.

---

## Authentication

### `login.php`
Shows the login form where users enter their username and password.

### `login_process.php`
Checks the login information. If the username and password are correct, the user information is stored in a session and the user is redirected to the homepage.

### `register.php`
Shows the registration form for new users.

### `insert_user.php`
Receives the registration form data, checks the passwords, hashes the password, and creates a new user in the database.

### `logout.php`
Ends the current session and redirects the user back to the login page.

---

## User page

### `user.php`
Shows information about the currently logged-in user.

The page includes:
- user information
- projects the user belongs to
- reservations created by the user
- links to make a reservation, view project reservations, and view inventory history

---

## Reagent inventory

### `view_reagents.php`
Shows the current reagent inventory.

The page includes:
- reagent name
- catalog number
- category
- supplier
- lot number
- physical quantity
- reserved quantity
- available quantity
- storage condition
- storage location
- expiry date

The available quantity is calculated as:

```text
available quantity = physical quantity - active reserved quantity
```

All active reservations are included in the calculation, even if they belong to different projects.

### `transactions.php`
Shows inventory transaction history.

Transactions may include:
- usage
- received stock
- discarded stock
- stock adjustments

The page also shows the reagent, lot, user, project, quantity change, note, and date.

---

## Reagent management

### `add_reagent.php`
Form for adding a new reagent.

### `insert_reagent.php`
Receives the form data from `add_reagent.php` and inserts the reagent into the database.

---

## Location management

### `add_location.php`
Form for adding a new storage location.

A location can include:
- room
- storage unit
- shelf
- box

### `insert_location.php`
Receives the location form data and inserts the new location into the database.

---

## Reagent lot management

### `add_lot.php`
Form for adding a new lot of an existing reagent.

The user selects:
- reagent
- location

and enters:
- supplier
- lot number
- current quantity
- received date
- expiry date

### `insert_lot.php`
Creates the reagent lot and connects it to the selected reagent and location.

---

## Project management

### `add_project.php`
Form for creating a new project.

### `insert_project.php`
Creates the new project in the database.

### `add_project_member.php`
Allows an existing user to be added to an existing project.

### `insert_project_member.php`
Creates the connection between the selected user and project in the `project_members` table.

---

## Reservations

### `make_reservation.php`
Shows the reservation form.

The user can only choose projects they are a member of. The page also shows reagent lots together with their current available quantity.

The form includes:
- project
- reagent lot
- quantity
- needed date

### `insert_reservation.php`
Checks the reservation before saving it.

The file checks that:
- the user is logged in
- the user belongs to the selected project
- the lot exists
- the quantity is valid
- enough stock is available

Creating a reservation does not reduce the physical stock.

### `reservations.php`
Shows reservations from projects the current user belongs to.

Users can see reservations from other members of the same project, but only the owner of an active reservation can record usage or cancel it.

### `cancel_reservation.php`
Changes an active reservation to `CANCELLED`.

Cancelling a reservation does not change the physical quantity.

---

## Usage

### `record_usage.php`
Shows the selected active reservation and lets the user enter the quantity actually used.

### `insert_usage.php`
Processes the final usage.

It:
1. reduces the physical quantity
2. creates a `USAGE` transaction
3. changes the reservation status to `COMPLETED`

These changes are handled together in one database transaction.

Example:

```text
Reserved: 10 mL
Actually used: 6 mL

Physical stock decreases by 6 mL.
The reservation is completed.
The unused 4 mL becomes available again.
```

---

## Admin

### `admin.php`
Main page for admin functions.

Only users with the `admin` role can access it.

Current admin functions include:
- add reagent
- add reagent lot
- add location
- create project
- add project member
- view inventory
- view inventory history

---

## Documentation files

### `README.md`
General project information.

### `Structure.md`
Overview of the website and file structure.

---

## Files to review later

### `inventory.php`
This is an older inventory page. Most of its function has now been moved to `view_reagents.php`.

It can probably be removed later, but only after checking that no other file still links to it.

### `add_user.php`
This file should also be reviewed.

It can either:
- be removed if registration is handled only through `register.php`, or
- be kept as an admin page for creating users

---

## Current main workflow

```text
Register
  ↓
Login
  ↓
User session
  ↓
Join project
  ↓
Make reservation
  ↓
ACTIVE
  ├── Cancel → CANCELLED
  └── Record usage → COMPLETED
```

For inventory:

```text
Physical quantity
- Active reservations
= Available quantity
```

Actual usage changes the physical stock and is recorded in the inventory history.

---

## Possible additions later

Possible future functions include:
- reagent search
- reagent filters
- reagent detail page
- project overview page
- project detail page
- edit reagent
- edit reagent lot
- remove project member
- admin user management
- receive stock
- discard stock
- adjust stock
- edit user profile
- change password
- forgot password
- department news management
- request more stock
