Change Log - Version 2.0.0
Release Date: [Insert Date]

New Features:
- Added Expiration Date for Users
  - Users now have an expiration date field. Once the expiration date is reached, all generations (API calls, actions) are halted for the user until the expiration is extended or renewed.

- Form Input Validation
  - Comprehensive validation has been added to all form inputs to ensure correct data submission. This includes checks for usernames, passwords, account limits, API call limits, and expiration dates.

- Password Hashing
  - Passwords are now securely hashed using password_hash() before being stored in the database, providing stronger security for user credentials. This update ensures all stored passwords are encrypted and safe.

- CSRF Token Validation
  - Implemented CSRF protection to prevent Cross-Site Request Forgery attacks. A unique CSRF token is generated and validated for all form submissions, ensuring only legitimate requests are processed.

- Added Log Table for Input and Output Tokens, Image Retries, and Total Costs
  - A new log table has been added to track input and output tokens, image retries, and total costs based on GPT-4o-mini API costs. This allows for detailed tracking and reporting of API usage and associated costs.

- API Costs Calculation and Display
  - API costs are now calculated and displayed on the accounts page. This provides users with a clear view of their API usage costs, helping them manage their usage more effectively.

Improvements:
- Refactored PHP Logic to Helper Files
  - PHP logic has been further refactored to separate helper files, improving maintainability and reducing clutter in page-specific scripts. This restructuring enhances code readability and modularity, making it easier to manage and scale.

- Centralized Database Operations
  - Moved database queries from `status-lib.php` to `common-lib.php` to centralize all database operations. This change aims to improve code organization and maintainability by having a single point of management for database interactions.
