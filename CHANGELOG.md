Change Log - Version 2.0.0
Release Date: [Insert Date]

New Features:
Added Expiration Date for Users

Users now have an expiration date field. Once the expiration date is reached, all generations (API calls, actions) are halted for the user until the expiration is extended or renewed.
Form Input Validation

Comprehensive validation has been added to all form inputs to ensure correct data submission. This includes checks for usernames, passwords, account limits, API call limits, and expiration dates.
Password Hashing

Passwords are now securely hashed using password_hash() before being stored in the database, providing stronger security for user credentials. This update ensures all stored passwords are encrypted and safe.
