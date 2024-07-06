<p align="center">
  <img src="v-chatgpt-social-status-feeds.png" width="60%" alt="project-logo">
</p>
<p align="center">
    <h1 align="center">V-CHATGPT-SOCIAL-STATUS-FEEDS</h1>
</p>
<p align="center">
    <em>Automate Your Social Updates. Engage Effortlessly.</em>
</p>
<p align="center">
	<!-- local repository, no metadata badges. -->
<p>
<p align="center">
		<em>Developed with the software and tools below.</em>
</p>
<p align="center">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
</p>

<br><!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary><br>

- [📍 Overview](#-overview)
- [🧩 Features](#-features)
- [🗂️ Repository Structure](#️-repository-structure)
- [📦 Modules](#-modules)
- [🚀 Getting Started](#-getting-started)
  - [⚙️ Installation](#️-installation)
  - [🤖 Usage](#-usage)
  - [🧪 Tests](#-tests)
- [🛠 Project Roadmap](#-project-roadmap)
- [🎗 License](#-license)
</details>
<hr>

## 📍 Overview

V-chatgpt-social-status-feeds is a robust application designed to generate and manage automated social media status updates using the ChatGPT API. It facilitates user account creation, handles authentication, and enforces API usage limits. The platform supports scheduled tasks, real-time RSS feed generation, and secure login processes, ensuring reliable and timely updates. Enhancing user engagement, it provides features like status sharing, account management, and password changes, all while maintaining data integrity and security through session handling, input sanitization, and IP blacklisting. This project streamlines social media content generation and management for users across various platforms.

---

## 🧩 Features

|    |   Feature         | Description                                                                                                                           |
|----|-------------------|---------------------------------------------------------------------------------------------------------------------------------------|
| ⚙️  | **Architecture**  | The project uses a PHP-based architecture with well-defined modules for configuration, database interactions, and user management.    |
| 🔩 | **Code Quality**  | The codebase follows a consistent style, emphasizing readability and maintainability, with logical separation of concerns across files. |
| 📄 | **Documentation** | Documentation is embedded within the code and provides clear instructions for setup, configuration, and maintenance tasks.             |
| 🔌 | **Integrations**  | Integrates with the ChatGPT API for status generation, includes database connections, and utilizes cron jobs for automated tasks.      |
| 🧩 | **Modularity**    | The code is modular with dedicated files for configuration, forms, helpers, and libraries, enhancing reusability and ease of updates.   |
| 🧪 | **Testing**       | No explicit mention of testing frameworks, implying reliance on manual testing and code review to ensure functionality and stability.  |
| ⚡️  | **Performance**   | Efficient resource usage with caching strategies in `.htaccess` and controlled API call limits, ensuring responsive performance.     |
| 🛡️ | **Security**      | Implements security measures like IP blacklisting, session management, CSRF tokens, and input sanitization to protect data and access. |
| 📦 | **Dependencies**  | Relies on PHP, database libraries, and specific configuration, form, and utility scripts for comprehensive functionality.              |
| 🚀 | **Scalability**   | Designed to handle increasing traffic with automated scheduling, efficient database interactions, and caching strategies.              |

---

## 🗂️ Repository Structure

```sh
└── v-chatgpt-social-status-feeds/
    ├── LICENSE
    ├── README.md
    ├── images
    │   ├── header.png
    │   ├── ss-1.jpg
    │   ├── ss-10.jpg
    │   ├── ss-11.jpg
    │   ├── ss-2.jpg
    │   ├── ss-4.jpg
    │   ├── ss-6.jpg
    │   ├── ss-7.jpg
    │   ├── ss-8.jpg
    │   └── ss-9.jpg
    └── root
        ├── app
        ├── config.php
        ├── cron.php
        ├── db.php
        ├── lib
        └── public
```

---

## 📦 Modules

<details closed><summary>root</summary>

| File                          | Summary                                                                                                                                                                                                                                                                                                                                               |
| ---                           | ---                                                                                                                                                                                                                                                                                                                                                   |
| [config.php](root/config.php) | Define configuration settings including API keys, endpoints, model preferences, domain, system messages, image resizing constraints, and database connection details to support the ChatGPT API Status Generators functionality within the v-chatgpt-social-status-feeds repository.                                                                  |
| [db.php](root/db.php)         | Establishes database connections, creates essential tables, and populates initial data for IP blacklists, status updates, accounts, and users, ensuring the smooth initial setup of the ChatGPT social status feeds application. Executes table creation and inserts default records, finalizing the installation by updating the configuration file. |
| [cron.php](root/cron.php)     | Handles scheduled tasks by resetting API usage, running status updates, and clearing the IP blacklist. Manages status updates based on a cron schedule, cleans up outdated statuses, and enforces API call limits per user. Ensures timely updates and database maintenance within the v-chatgpt-social-status-feeds repository.                      |

</details>

<details closed><summary>root.public</summary>

| File                                 | Summary                                                                                                                                                                                                                                                                                                                                                                         |
| ---                                  | ---                                                                                                                                                                                                                                                                                                                                                                             |
| [index.php](root/public/index.php)   | Serves as the main entry point for the ChatGPT API Status Generators admin interface, managing session initialization, page loading, and inclusion of essential configuration and utility files. Provides navigation and content display for various dashboard sections, catering to different user roles and ensuring responsive design with embedded scripts and stylesheets. |
| [.htaccess](root/public/.htaccess)   | The `.htaccess` file in `root/public` manages URL redirection, serves default images when requested ones are missing, and optimizes performance through cache control headers. These features enhance user experience and ensure efficient resource loading within the v-chatgpt-social-status-feeds repositorys web application architecture.                                  |
| [login.php](root/public/login.php)   | The login.php file facilitates the authentication process for admin users by presenting a login interface and validating credentials. Integrating with configuration, database, and authentication libraries, it streamlines access management within the ChatGPT API Status Generator system, ensuring secure and controlled administrative entry.                             |
| [robots.txt](root/public/robots.txt) | Regulate web crawlers access with an autogenerated `robots.txt` to control crawl rates and manage server load, ensuring efficient indexing and performance. This configuration directly supports the repositorys goal of providing a robust and scalable social status feed platform by mitigating potential overload from web crawling activities.                             |
| [feeds.php](root/public/feeds.php)   | Generate an RSS feed based on user accounts for the ChatGPT API, leveraging configuration, database functions, and RSS feed utilities. Validate required query parameters to ensure security before delivering the feed. The file integrates seamlessly with the parent repositorys architecture for managing social status feeds.                                              |

</details>

<details closed><summary>root.public.images</summary>

| File                                      | Summary                                               |
| ---                                       | ---                                                   |
| [index.php](root/public/images/index.php) | Prevent unauthorized access by terminating execution. |

</details>

<details closed><summary>root.app.forms</summary>

| File                                                    | Summary                                                                                                                                                                                                                                                                                                                                                                                |
| ---                                                     | ---                                                                                                                                                                                                                                                                                                                                                                                    |
| [home-forms.php](root/app/forms/home-forms.php)         | Handles the creation and deletion of social status updates by interacting with the database and the filesystem. Validates user API call limits, manages image files associated with statuses, and updates user information accordingly, ensuring a smooth user experience within the ChatGPT-powered social status feed application.                                                   |
| [info-forms.php](root/app/forms/info-forms.php)         | Handles user password change requests, verifies password match, interacts with the database to update passwords, and provides success or error messages. Integrates into the broader ChatGPT API Status Generator project, enhancing user account management within the social status feeds application architecture.                                                                  |
| [users-forms.php](root/app/forms/users-forms.php)       | Manages user-related operations including creation, modification, and deletion of user accounts. Validates user credentials, updates database records, and handles session management for user login. Ensures appropriate user directory creation for storing images, essential for maintaining the ChatGPT API social status feeds platform’s user data integrity and functionality.  |
| [accounts-forms.php](root/app/forms/accounts-forms.php) | Manage user accounts related to social media status updates, supporting creation, modification, and deletion. Validate user inputs, ensure correct formatting, and interact with the database to update account records. Handle scheduling options and data validation, ensuring seamless integration with the repositorys overall ChatGPT API-driven status generation functionality. |

</details>

<details closed><summary>root.app.helpers</summary>

| File                                                        | Summary                                                                                                                                                                                                                                                                                                                          |
| ---                                                         | ---                                                                                                                                                                                                                                                                                                                              |
| [accounts-helper.php](root/app/helpers/accounts-helper.php) | Provides user account details by fetching information from the session and integrating it into the user interface. Enhances the display of key metrics like total accounts, maximum API calls, and used API calls, contributing to better user experience and account management within the ChatGPT API status generator.        |
| [home-helper.php](root/app/helpers/home-helper.php)         | Generates share buttons for status updates, enabling users to copy text, download images, and delete statuses. Integrates seamlessly into the home page, leveraging SVG icons for visual cues and incorporating security measures like CSRF tokens to ensure safe interactions within the ChatGPT API Status Generator platform. |

</details>

<details closed><summary>root.app.pages</summary>

| File                                        | Summary                                                                                                                                                                                                                                                                                                                                                                    |
| ---                                         | ---                                                                                                                                                                                                                                                                                                                                                                        |
| [info.php](root/app/pages/info.php)         | Provide users with a form to change their password within the ChatGPT API Status Generator application, ensuring secure and straightforward credential updates while displaying any relevant messages.                                                                                                                                                                     |
| [accounts.php](root/app/pages/accounts.php) | Manages account configurations and scheduling for social media updates using the ChatGPT API. Enables adding or updating account details, setting posting schedules, and configuring prompts for status updates. Facilitates account management through a user-friendly interface, including forms and interactive elements for efficient social media content generation. |
| [users.php](root/app/pages/users.php)       | Manage user accounts by providing forms to add, update, or delete users. Display users with editable details and facilitate role assignments and API call limits. Offer functionality to impersonate users for administrative purposes. Ensure user actions are secured with CSRF tokens.                                                                                  |
| [home.php](root/app/pages/home.php)         | Generates and displays status updates for user accounts, offering options to view the feed or create new statuses. Supports status sharing through copy or download actions. Integrates with the parent repositorys architecture by handling user-specific data retrieval and interaction, enhancing user engagement on the platform.                                      |

</details>

<details closed><summary>root.lib</summary>

| File                                      | Summary                                                                                                                                                                                                                                                                                                                                                              |
| ---                                       | ---                                                                                                                                                                                                                                                                                                                                                                  |
| [rss-lib.php](root/lib/rss-lib.php)       | Generate RSS feeds for status updates from specified accounts, enabling users to access and subscribe to real-time status updates. Integrates with the database to fetch account information and status updates, and formats the data in RSS 2.0 standards, ensuring seamless information dissemination and accessibility.                                           |
| [auth-lib.php](root/lib/auth-lib.php)     | Manages user authentication processes, including login and logout functionalities, session handling, CSRF token generation, and IP-based blacklisting for enhanced security. Integral to ensuring secure access and user session integrity within the ChatGPT API Status Generators framework.                                                                       |
| [common-lib.php](root/lib/common-lib.php) | Provide essential utility functions for sanitizing inputs, handling sessions, managing IP blacklists, retrieving user and account information, and updating status and API usage, thereby supporting the security, user management, and data integrity mechanisms in the ChatGPT API Status Generator within the broader v-chatgpt-social-status-feeds architecture. |
| [status-lib.php](root/lib/status-lib.php) | Generate automated social media statuses tailored to different platforms, incorporating user-specific prompts, links, and images, and optionally adding relevant hashtags. Retrieve and store content via API interactions and save it along with associated metadata in the database for efficient management and deployment.                                       |
| [load-lib.php](root/lib/load-lib.php)     | Load-lib.php ensures secure access and dynamic content loading in the ChatGPT social status feeds application. It manages session handling, IP blacklisting, and user authentication while conditionally including helper, form, and page files based on user roles, ensuring a personalized and secure user experience.                                             |

</details>

---

## 🚀 Getting Started

**System Requirements:**

* **PHP**: `version 3.10`

### ⚙️ Installation

<h4>From <code>source</code></h4>

> 1. Clone the v-chatgpt-social-status-feeds repository:
>
> ```console
> $ git clone ../v-chatgpt-social-status-feeds
> ```
>
> 2. Change to the project directory:
> ```console
> $ cd v-chatgpt-social-status-feeds
> ```
>
> 3. Install the dependencies:
> ```console
> $ composer install
> ```

### 🤖 Usage

<h4>From <code>source</code></h4>

> Run v-chatgpt-social-status-feeds using the command below:
> ```console
> $ php main.php
> ```

### 🧪 Tests

> Run the test suite using the command below:
> ```console
> $ vendor/bin/phpunit
> ```

---

## 🛠 Project Roadmap

- [X] `► INSERT-TASK-1`
- [ ] `► INSERT-TASK-2`
- [ ] `► ...`

---

## 🎗 License

This project is protected under the MIT License

---

[**Return**](#-overview)

---
