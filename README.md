<p align="center">
  <img src="v-chatgpt-social-status-feeds.png" width="60%" alt="project-logo">
</p>
<p align="center">
    <h1 align="center"></h1>
</p>
<p align="center">
    <em>Empower Your Voice, Manage Your Status!</em>
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
- [📦 Modules](#-modules)
- [🚀 Getting Started](#-getting-started)
  - [⚙️ Installation](#️-installation)
  - [🤖 Usage](#-usage)
- [🛠 Project Roadmap](#-project-roadmap)
- [🎗 License](#-license)
</details>
<hr>

## 📍 Overview

The ChatGPT API Status Generator is a web application designed to facilitate the management and monitoring of user accounts and their corresponding social media statuses. It provides a user-friendly dashboard for account creation, updates, and deletions while ensuring secure authentication and robust session management. The project generates personalized RSS feeds, streamlines API interactions, and automates routine maintenance tasks to optimize performance. With its seamless integration of user management features and content generation capabilities, the ChatGPT API Status Generator offers significant value by enhancing user engagement and simplifying the process of sharing dynamic content across platforms.

---

## 🧩 Features

|     | Feature           | Description                                                                                                                                                                                       |
| --- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ⚙️   | **Architecture**  | The project utilizes a PHP-based architecture with a clear directory structure that separates configuration, database, and public-facing components, ensuring modularity and maintainability.     |
| 🔩   | **Code Quality**  | The code follows a consistent style with clear naming conventions, facilitating readability and collaboration. It incorporates robust error handling and input validation to enhance reliability. |
| 📄   | **Documentation** | Documentation is present but mostly inline within the codebase. Each file includes comments explaining functionality, which aids developers in understanding and using the system effectively.    |
| 🔌   | **Integrations**  | The project integrates with the ChatGPT API for generating statuses and supports RSS feed generation, enhancing its functionality and user engagement with external systems.                      |
| 🧩   | **Modularity**    | The codebase is highly modular, segmented into distinct libraries and helper functions that promote reusability across various functionalities, enabling easier updates and feature additions.    |
| 🧪   | **Testing**       | The project does not explicitly mention testing frameworks, suggesting a potential area for improvement. Automated testing could enhance reliability and simplify maintenance.                    |
| ⚡️   | **Performance**   | Designed for efficiency, the project implements caching mechanisms and optimized database queries, ensuring responsive performance even with multiple users and status updates.                   |
| 🛡️   | **Security**      | Security measures include session management, input sanitization, and an IP blacklist, which collectively help protect against unauthorized access and ensure data integrity.                     |
| 📦   | **Dependencies**  | Key dependencies include PHP for server-side logic and configuration files (e.g. .htaccess) for URL management and caching, which are critical for application routing and performance.           |
| 🚀   | **Scalability**   | The architecture supports scalability through efficient database management and modular code, allowing the handling of increased user traffic and resource demands as the user base grows.        |
```

---

## 🗂️ Repository Structure

```sh
└── /
    ├── LICENSE
    ├── README.md
    ├── root
    │   ├── app
    │   │   ├── forms
    │   │   │   ├── accounts-forms.php
    │   │   │   ├── home-forms.php
    │   │   │   ├── info-forms.php
    │   │   │   └── users-forms.php
    │   │   ├── helpers
    │   │   │   ├── accounts-helper.php
    │   │   │   └── home-helper.php
    │   │   └── pages
    │   │       ├── accounts.php
    │   │       ├── home.php
    │   │       ├── info.php
    │   │       └── users.php
    │   ├── config.php
    │   ├── cron.php
    │   ├── db.php
    │   ├── lib
    │   │   ├── auth-lib.php
    │   │   ├── common-lib.php
    │   │   ├── load-lib.php
    │   │   ├── rss-lib.php
    │   │   └── status-lib.php
    │   └── public
    │       ├── .htaccess
    │       ├── assets
    │       │   ├── css
    │       │   ├── images
    │       │   ├── index.php
    │       │   └── js
    │       ├── favicon.ico
    │       ├── feeds.php
    │       ├── images
    │       │   └── index.php
    │       ├── index.php
    │       ├── login.php
    │       └── robots.txt
    └── v-chatgpt-social-status-feeds.png
```

---

## 📦 Modules

<details closed><summary>root</summary>

| File                          | Summary                                                                                                                                                                                                                                                                                                                                                            |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| [config.php](root/config.php) | Defines crucial configuration settings for the ChatGPT API Status Generator, including API keys, endpoints, model preferences, and database connection details. Establishes parameters guiding the AIs behavior, such as output temperature and content scope, thereby ensuring seamless integration and functionality within the overall repository architecture. |
| [db.php](root/db.php)         | Establishes a robust database connection and initializes essential tables for user accounts and status updates. Facilitates the installation process by verifying application status and creating necessary structures, ensuring that the repository can manage user data and interactions effectively within its broader application framework.                   |
| [cron.php](root/cron.php)     | Handles scheduled tasks essential for maintaining system performance, including API usage resets, status updates, IP blacklist clearing, and image purging. Integrates seamlessly into the repositorys architecture to optimize functionality and ensure efficient management of user accounts and resources within the ChatGPT API project.                       |

</details>

<details closed><summary>root.public</summary>

| File                                 | Summary                                                                                                                                                                                                                                                                                                                                                     |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [index.php](root/public/index.php)   | Serves as the main entry point for the admin interface of the ChatGPT API Status Generator, providing a dashboard that allows users to navigate through different sections, manage account information, and access status feeds while ensuring seamless user experience and session management.                                                             |
| [.htaccess](root/public/.htaccess)   | Facilitates URL rewriting and caching mechanisms within the web application, ensuring user-friendly navigation and optimized resource loading. Directs requests to relevant pages while serving a default image when necessary, enhancing performance and user experience across the repositorys structure.                                                 |
| [login.php](root/public/login.php)   | Facilitates user authentication for the ChatGPT API Status Generator by providing an intuitive login interface. Integrates session management and input handling while ensuring secure access to the administrative features within the repositorys broader architecture, enhancing user experience and system protection.                                  |
| [robots.txt](root/public/robots.txt) | Enables search engine management by instructing crawlers on how to interact with the website. Establishes a crawl-delay to optimize server performance while ensuring important content remains accessible, thereby enhancing user experience and aligning with the overall architecture’s goal of efficient resource utilization and site discoverability. |
| [feeds.php](root/public/feeds.php)   | Generates an RSS feed for the ChatGPT API based on user accounts, facilitating direct content delivery. It ensures security through parameter validation, enhances user experience by providing account-specific feeds, and integrates essential configuration and utility functions within the repository’s architecture.                                  |

</details>

<details closed><summary>root.public.images</summary>

| File                                      | Summary                                                                                                                                                                                                                                                                                                                     |
| ----------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [index.php](root/public/images/index.php) | Prevents direct access to the images directory by terminating script execution. This security measure is crucial in the parent repositorys architecture, ensuring that sensitive resources remain protected from unauthorized access, thereby enhancing the overall integrity and safety of the applications public assets. |

</details>

<details closed><summary>root.app.forms</summary>

| File                                                    | Summary                                                                                                                                                                                                                                                                                                                                                                       |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [home-forms.php](root/app/forms/home-forms.php)         | Facilitates user interactions on the home page by enabling status generation and deletion. It manages API call limits while retrieving and updating user status information. By integrating with the database, it ensures efficient status management, enhancing the overall user experience in the ChatGPT API project.                                                      |
| [info-forms.php](root/app/forms/info-forms.php)         | Facilitates user password management within the ChatGPT API by handling password changes securely. It ensures that password confirmation is validated and updates the database accordingly, enhancing user experience and account security in the broader application architecture. This feature integrates seamlessly with the user management components of the repository. |
| [users-forms.php](root/app/forms/users-forms.php)       | Facilitates user management by enabling the creation, modification, and deletion of user accounts within the ChatGPT API framework. Ensures robust validation of user inputs while providing session management to enhance user experience, thus integrating seamlessly into the applications overall architecture for efficient user authentication and administration.      |
| [accounts-forms.php](root/app/forms/accounts-forms.php) | Facilitates account management within the ChatGPT API project by allowing users to create, update, or delete their accounts. It validates user inputs, manages scheduling options, and ensures seamless integration with the database for storing account details, thereby enhancing user interaction and experience.                                                         |

</details>

<details closed><summary>root.app.helpers</summary>

| File                                                        | Summary                                                                                                                                                                                                                                                                                                                              |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| [accounts-helper.php](root/app/helpers/accounts-helper.php) | Generates account details for users by fetching and formatting essential information such as total accounts and API call limits. This functionality enhances the user experience within the application, allowing easy access to relevant account status, thus supporting the overall architecture of the ChatGPT API ecosystem.     |
| [home-helper.php](root/app/helpers/home-helper.php)         | Generates an RSS feed for ChatGPT API status updates, consolidating information from multiple user accounts if requested. It retrieves status updates from a database, organizes them chronologically, and constructs an RSS XML output, enhancing user experience by providing a structured format for monitoring account statuses. |

</details>

<details closed><summary>root.app.pages</summary>

| File                                        | Summary                                                                                                                                                                                                                                                                                                                                            |
| ------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [info.php](root/app/pages/info.php)         | Facilitates user account management by providing a password change feature within the ChatGPT API environment. This page is crucial for enhancing security and user experience, allowing users to update their credentials seamlessly while integrating with the broader application framework for robust session management and feedback display. |
| [accounts.php](root/app/pages/accounts.php) | Facilitates account management within the ChatGPT API, allowing users to add, update, and delete social media account details. It dynamically generates forms for input and incorporates session-based CSRF protection, enhancing security while providing a seamless user experience in managing status updates and schedules.                    |
| [users.php](root/app/pages/users.php)       | Facilitates user management within the ChatGPT API project by providing an interface to add, update, and delete users. It dynamically displays user information and ensures secure interactions through CSRF protection, thereby enhancing user account administration and overall system functionality.                                           |
| [home.php](root/app/pages/home.php)         | Provides a dynamic interface for displaying user account statuses in a structured manner. It facilitates account management by allowing users to view, generate, and share statuses, ensuring a seamless experience while managing multiple accounts within the broader architecture of the ChatGPT API Status Generator repository.               |

</details>

<details closed><summary>root.lib</summary>

| File                                      | Summary                                                                                                                                                                                                                                                                                                                                                                         |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [rss-lib.php](root/lib/rss-lib.php)       | Generates an RSS feed for user status updates, allowing users to retrieve either all accounts or a specific accounts statuses. It retrieves data from the database, formats it into XML, and sets the appropriate content headers, enhancing the overall accessibility of user-generated content within the ChatGPT API architecture.                                           |
| [auth-lib.php](root/lib/auth-lib.php)     | Authenticates users for the ChatGPT API by managing login sessions, handling logout requests, and ensuring security through CSRF tokens and session regeneration. It enhances user experience by redirecting to appropriate pages based on authentication success or failure, integrating seamlessly within the repository’s overall architecture.                              |
| [common-lib.php](root/lib/common-lib.php) | Enhances user experience and security within the ChatGPT API by providing essential utility functions for input sanitization, session management, IP blacklist handling, and user account management. These features ensure safe interactions, efficient user data retrieval, and robust status updates, contributing to the overall stability of the application architecture. |
| [status-lib.php](root/lib/status-lib.php) | Generates social media statuses by integrating account information, API calls for content and images, and hashtag optimization based on platform requirements. This functionality enhances user engagement within the ChatGPT API repository, enabling dynamic and visually appealing social media sharing that aligns with user preferences.                                   |
| [load-lib.php](root/lib/load-lib.php)     | Facilitates user access control and dynamic page loading in the ChatGPT API project. It manages user permissions based on their roles, checks against IP blacklists, and includes relevant helpers and forms while ensuring that sensitive pages remain accessible only to authorized users, thereby enhancing security and user experience.                                    |

</details>

---

## 🚀 Getting Started

**System Requirements:**

* **PHP**: `version 7.4+`

### ⚙️ Installation

Upload to webserver. Create a MySQL database. Update `config.php`, launch the site, and it will auto-install.
Make sure to set `public/` as your new webroot.

### 🤖 Usage

Go to the installed URL. Login with the default credentials:

admin
admin

You can then change the password and create other users.

Accounts are more like "social media accounts". Each one has a link and prompt for status generation.
You can set days and times for each to generate.

Use RSS feeds with tools like IFTTT to easily update social media.

---

## 🛠 Project Roadmap

- [X] `► Added cron to clear old images`
- [X] `► Added server side ability to prevent image 404s by using a default image`
- [X] `► Added Web Share API`
- [X] `► Made accounts collapsible for better mobile usage`
- [X] `► Added OmniFeed, a user feed integrated with all feeds`

---

## 🎗 License

This project is protected under the [MIT License](./LICENSE) License.

---
