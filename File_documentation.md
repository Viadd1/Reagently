# Reagently File Overview

## Core files

Fundamental files that are included/required in other files.

### `db.php`
Creates the connection to the MySQL database. Other PHP files include this file when they need database access.

### `init.php`
Starts or resumes existing session and defines base path and base URL variables. Also alters session cookie parameters if the website is run through HTTPS. Most other PHP files require this file.

### `navbar.php`
A file containing the navigation bar, which is included in most frontend and some database connection files.

### `style.css`
Contains the shared styling for the website, including the header, navigation bar, buttons, homepage sections, and other layout settings.

## Main pages
Pages included in the navigation bar.

### `index.php`
The main homepage of Reagently. It shows the navigation menu, a welcome message, shortcuts to common functions, and recent updates.

### `admin.php`
Main page for admin functions. Only users with the `admin` role can access it. A full list of all admin functions can be found below.

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

### `user.php`
Shows information about the currently logged-in user.

The page includes:
- user information
- projects the user belongs to
- reservations created by the user
- links to make a reservation, view project reservations, and view inventory history

---

## Authentication
Files related to user authentication.

### `login.php`
Shows the login form where users enter their username and password.

### `captcha.php`
Generates a CAPTCHA image which is shown on the login screen.

### `login_process.php`
Checks the login information. If the username and password are correct, the user information is stored in a session and the user is redirected to the homepage.

### `register.php`
Shows the registration form for new users.

### `terms_of_service.php`
Terms of service document shown when registering.

### `insert_user.php`
Receives the registration form data, checks the passwords, hashes the password, and creates a new user in the database.

### `verify_email.php`
When a user registers an account, a verification email is sent. If the user clicks the link, they are taken to this file where the user is verified.

### `forgot_password.php`
Form for restoring the password to a Reagently user account. If the provided email address is linked to an account, this file sends an email to that address.

### `reset_password.php`
When the user clicks a link from a password restoration email, they can change their password here.

### `logout.php`
Ends the current session and redirects the user back to the login page.

### `cookies_consent.php`
When the user is logged in, a form for accepting or denying cookies is displayed.

---

## User pages
Pages that all users can access and use.

### `make_reservation.php`
Shows the reservation form.

The user can only choose projects they are a member of. The page also shows reagent lots together with their current available quantity.

The form includes:
- project
- reagent lot
- quantity
- needed date

### `reservations.php`
Shows reservations from projects the current user belongs to.

Users can see reservations from other members of the same project, but only the owner of an active reservation can record usage or cancel it.

### `record_usage.php`
Shows the selected active reservation and lets the user enter the quantity actually used.

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
Pages related to adding and modifying existing reagents, lots, and locations. Only admins can access these files.

### `add_reagent.php`
Form for adding a new reagent.

### `add_location.php`
Form for adding a new storage location.

A location can include:
- room
- storage unit
- shelf
- box

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

### `import_csv.php`
Import reagents or reagent lots from a pre-constructed CSV file. The file must be of a particular format, refer to example_data for more details.

### `manage_reagents.php`
Edit or delete existing reagents or give reagents nicknames. Also, quantities of specific reagent lots can be updated.

---

## Project management
Pages related to adding and modifying projects and project members. Only admins can access these files.

### `add_project.php`
Form for creating a new project.

### `add_project_member.php`
Allows an existing user to be added to an existing project.

### `manage_projects.php`
Edit or delete existing projects, or remove project members.

---

## Inventory management (admin)
Pages related to the current reagent inventory. Only admins can access these files.

### `view_order_requests.php`
Shows all current order requests from users, for example if a reagent is missing or if they want to reserve more than the reservable amount. The admin is given the option to either accept or deny requests, and the user is then notified by email.

### `expiring_reagents.php`
Shows reagent lots that have already expired or that will expire within the next month. If there are any, a notification generated by expiry_notice.php appears on the main page.


## User Management (admin)
Pages related to managing user accounts. Only admins can access these files.

### `approve_users.php`
When a user registers and has selected a specific account as their admin, their account must be approved by that admin on this page.

### `view_users.php`
Search for specific users and edit their personal information (or delete their account)

### `supervised_users.php`
A list of all users under that specific admin's supervision.

## Database connections
There are a number of files that take submitted data from forms and insert, update, or delete  records in the database. For safety, these files can only be accessed through a POST method form. Some pages of particular interest are listed below.

### `insert_reservation.php`
Checks the reservation before saving it.

The file checks that:
- the user is logged in
- the user belongs to the selected project
- the lot exists
- the quantity is valid
- enough stock is available

Creating a reservation does not reduce the physical stock.

### `cancel_reservation.php`
Changes an active reservation to `CANCELLED`.

Cancelling a reservation does not change the physical quantity.

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

## Documentation files

### `README.md`
General project information.

### `Structure.md`
Overview of the website and file structure.

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
