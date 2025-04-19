<p align="center">
    <img src="v-chatgpt-social-status-feeds.png" align="center" width="100%">
</p>
<p align="center"><h1 align="center"><code>❯v-chatgpt-social-status-feeds</code></h1></p>
<p align="center">
	<em>Streamline Your Social Statuses, Effortlessly!</em>
</p>
<p align="center">
	<!-- local repository, no metadata badges. --></p>
<p align="center">Built with the tools and technologies:</p>
<p align="center">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
</p>
<br>

## Table of Contents

I. [ Overview](#-overview)
II. [ Features](#-features)
III. [ Project Structure](#-project-structure)
IV. [ Getting Started](#-getting-started)
V. [ Additional Information](#-additional-information)
VI. [ Changelog](#-changelog)
VII. [ License](#-license)

---

## Overview

v-chatgpt-social-status-feeds is a modular PHP application for managing, scheduling, and distributing social media status updates. It features user authentication, account management, status scheduling, and real-time RSS feeds, all with a focus on security and extensibility. Built for social media managers and developers, it streamlines multi-account status posting and automation.

Version 2.0.0 introduces improvements such as dedicated classes for all database operations, a more intuitive user interface, and enhanced user settings for prompt customization. The API schema is now more structured, and the platform is more robust and user-friendly.

- **CSRF Protection:** All forms include CSRF tokens to prevent cross-site request forgery attacks.
- **Input Validation:** User inputs are validated and sanitized to prevent SQL injection and XSS attacks.
- **Session Management:** Secure session handling to prevent session fixation and hijacking.
- **IP Blacklisting:** Monitors and blacklists suspicious IP addresses to prevent brute-force attacks.
- **Efficient Database Queries:** Uses optimized SQL queries and indexing to ensure fast data retrieval.
- **Modular Classes:** Core logic is organized into classes such as Database, UserHandler, AccountHandler, StatusHandler, UtilityHandler, and ErrorHandler for maintainability and scalability.

In upcoming updates, the cron system will be optimized to handle large numbers of statuses efficiently, and additional security improvements are planned.

## Features

|     |      Feature      | Summary                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| :-- | :---------------: | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ⚙️  | **Architecture**  | <ul><li>Uses a modular architecture with dedicated classes for <code>Database</code>, <code>UserHandler</code>, <code>AccountHandler</code>, <code>StatusHandler</code>, <code>UtilityHandler</code>, and <code>ErrorHandler</code> (see <code>root/classes/</code>).</li><li>Configuration is centralized in <code>root/config.php</code>.</li><li>Autoloading is handled by <code>root/autoload.php</code> for efficient class management.</li><li>Automated tasks are managed via <code>root/cron.php</code> and server cron jobs.</li></ul> |
| 🔩  | **Code Quality**  | <ul><li>Follows best practices for code organization and modularity.</li><li>Centralized database management using PDO (<code>Database.php</code>).</li><li>Comprehensive error handling via <code>ErrorHandler.php</code>.</li><li>Detailed inline comments and documentation in key files.</li></ul>                                                                                                                                                                                                                                          |
| 📄  | **Documentation** | <ul><li>Includes usage commands and installation steps for onboarding.</li><li>Primarily written in <code>PHP</code> with <code>SQL</code> for database setup and <code>text</code> files for configuration.</li></ul>                                                                                                                                                                                                                                                                                                                          |
| 🔌  | **Integrations**  | <ul><li>Supports integration with social media platforms for status updates via <code>status-lib.php</code>.</li><li>Provides RSS feeds for real-time updates (<code>rss-lib.php</code>).</li><li>Implements user authentication and session management (<code>auth-lib.php</code>).</li></ul>                                                                                                                                                                                                                                                  |
| 🧩  |  **Modularity**   | <ul><li>Components are organized into separate classes for maintainability and scalability.</li><li>Autoloading minimizes manual file loading.</li><li>Code is reusable across the application.</li></ul>                                                                                                                                                                                                                                                                                                                                       |
| 🔒  |   **Security**    | <ul><li>CSRF protection on all forms.</li><li>Input validation and sanitization throughout.</li><li>Session management and IP blacklisting for brute-force protection.</li></ul>                                                                                                                                                                                                                                                                                                                                                                |

---

## Project Structure

```sh
└── /
    ├── LICENSE
    ├── README.md
    ├── root
    │   ├── app
    │   │   ├── forms
    │   │   ├── helpers
    │   │   └── pages
    │   ├── autoload.php
    │   ├── classes
    │   │   ├── AccountHandler.php
    │   │   ├── Database.php
    │   │   ├── ErrorHandler.php
    │   │   ├── StatusHandler.php
    │   │   ├── UserHandler.php
    │   │   └── UtilityHandler.php
    │   ├── config.php
    │   ├── cron.php
    │   ├── install.sql
    │   ├── lib
    │   │   ├── auth-lib.php
    │   │   ├── load-lib.php
    │   │   ├── rss-lib.php
    │   │   └── status-lib.php
    │   └── public
    │       ├── .htaccess
    │       ├── assets
    │       ├── favicon.ico
    │       ├── feeds.php
    │       ├── images
    │       ├── index.php
    │       ├── install.php
    │       ├── login.php
    │       └── robots.txt
    └── v-chatgpt-social-status-feeds.png
```

### Project Index

<details open><!-- root Submodule -->
		<summary><b>root</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='/root/config.php'>config.php</a></b></td>
				<td>- Configuration settings streamline the management of critical parameters for the ChatGPT API Status Generator<br>- Key elements include API authentication, endpoint definitions, model specifications, and temperature settings guiding the AI's creativity<br>- Additionally, it establishes database connection details, SMTP settings for email notifications, and security provisions like CSRF token generation, ensuring a robust and responsive architecture within the overall project structure.</td>
			</tr>
			<tr>
				<td><b><a href='/root/autoload.php'>autoload.php</a></b></td>
				<td>- Autoloading functionality streamlines the inclusion of class files within the codebase, enhancing modularity and reducing the manual overhead of file management<br>- By automatically loading class definitions from a specified directory, it supports code organization and promotes clean architecture, making it easier to maintain and extend the project as new classes are added.</td>
			</tr>
			<tr>
				<td><b><a href='/root/install.sql'>install.sql</a></b></td>
				<td>- Establishes the foundational database structure necessary for user management and content status updates within the application<br>- It ensures the existence of critical tables, such as ip_blacklist, status_updates, accounts, and users, while also inserting a default admin user if the users table initially does not exist<br>- This setup is essential for maintaining the application's functionality and user data integrity.</td>
			</tr>
			<tr>
				<td><b><a href='/root/cron.php'>cron.php</a></b></td>
				<td>- Cron functionality within the ChatGPT API project manages essential scheduled tasks to ensure system efficiency and user experience<br>- It enables the resetting of API usage, executing status updates, clearing the IP blacklist, and purging outdated images<br>- This proactive maintenance aids in resource management, prevents system overload, and ensures compliance with user limits and subscription conditions, thereby supporting the overall architecture's robustness.</td>
			</table>
			<details>
				<summary><b>classes</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='/root/classes/UtilityHandler.php'>UtilityHandler.php</a></b></td>
						<td>- UtilityHandler centralizes the management of IP blacklist operations within the ChatGPT API project<br>- It facilitates updating failed login attempts, checking the blacklist status of IP addresses, and clearing the blacklist as needed<br>- Additionally, it handles displaying and clearing session messages, contributing to a user-friendly interface while maintaining system security through effective IP monitoring.</td>
					</tr>
					<tr>
						<td><b><a href='/root/classes/StatusHandler.php'>StatusHandler.php</a></b></td>
						<td>- StatusHandler facilitates the management and retrieval of status updates within the ChatGPT API project<br>- It enables users to save, delete, and fetch their status information, as well as check for existing statuses within specific time frames<br>- By serving as a central component for handling status interactions, it ensures a streamlined experience for users managing their account-related updates.</td>
					</tr>
					<tr>
						<td><b><a href='/root/classes/UserHandler.php'>UserHandler.php</a></b></td>
						<td>- UserHandler manages user-related operations in the ChatGPT API project, facilitating actions such as retrieving, saving, updating, and deleting user data<br>- It provides essential functionalities for user management, including account verification, API call tracking, and profile updates, ensuring robust interaction with user data within the overall system architecture<br>- This class plays a vital role in maintaining user data integrity and operational efficiency.</td>
					</tr>
					<tr>
						<td><b><a href='/root/classes/AccountHandler.php'>AccountHandler.php</a></b></td>
						<td>- AccountHandler streamlines the management of user accounts within the ChatGPT API project<br>- It facilitates essential operations such as retrieving account information, checking account existence, creating, updating, and deleting accounts<br>- By providing these functionalities, it enhances the overall architecture of the codebase, ensuring efficient handling of user data and maintaining the integrity of account-related processes throughout the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/classes/Database.php'>Database.php</a></b></td>
						<td>- Database management is streamlined through a class that centralizes the connection and interaction with the database using PDO<br>- It facilitates the execution of SQL queries, handles parameter binding, and manages transaction control<br>- By ensuring a single instance of the database connection, it enhances efficiency and error handling<br>- This functionality is crucial for the overall architecture of the ChatGPT API project, enabling smooth data operations.</td>
					</tr>
					<tr>
						<td><b><a href='/root/classes/ErrorHandler.php'>ErrorHandler.php</a></b></td>
						<td>- ErrorHandler ensures robust error management throughout the application by registering handlers for errors, exceptions, and shutdown events<br>- It transforms PHP errors into exceptions, logs uncaught exceptions, and manages critical shutdown errors, thereby enhancing the user experience with graceful error responses<br>- Additionally, it maintains a log of error messages, which aids in debugging and monitoring system health within the overall codebase architecture.</td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>public</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='/root/public/index.php'>index.php</a></b></td>
						<td>- Serves as the main dashboard for the ChatGPT API Status Generator, facilitating user interaction and navigation within the application<br>- It manages session security, provides a responsive design with integrated resources, and displays user-specific information and options<br>- Additionally, it supports administrative functionality, enhancing user experience while ensuring seamless access to various features of the API project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/.htaccess'>.htaccess</a></b></td>
						<td>- Provides URL rewriting and caching rules to enhance web application performance and user experience<br>- It serves a default image for missing PNG requests, redirects specific URLs to improve navigation, and ensures efficient resource loading through caching strategies<br>- This approach simplifies access to essential pages while managing resource delivery effectively within the overall project architecture.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/login.php'>login.php</a></b></td>
						<td>- Facilitates user authentication for the ChatGPT API project by providing a secure login interface<br>- It incorporates session management, error handling, and responsive design elements while linking to external stylesheets for improved user experience<br>- This page is essential for accessing administrative features, ensuring that only authorized users can log in and manage the API's status and functionality.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/robots.txt'>robots.txt</a></b></td>
						<td>- Defines crawling instructions for web robots within the project, promoting effective and controlled indexing of site content<br>- By specifying a crawl delay, it aims to manage server load and optimize the interaction between search engines and the website<br>- This contributes to the overall web presence strategy, ensuring that important resources are accessible while maintaining performance.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/feeds.php'>feeds.php</a></b></td>
						<td>- Generates an RSS feed for the ChatGPT API by utilizing user account information<br>- It ensures the presence and validity of required parameters, sanitizes input to prevent security issues, and executes the feed output while handling potential exceptions<br>- This functionality enhances user engagement by providing a streamlined way for account owners to distribute updates via RSS feeds, contributing to the project's overall interactivity and user experience.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/install.php'>install.php</a></b></td>
						<td>- Installation of the database is facilitated through a PHP script that establishes a connection to the database using credentials from a configuration file<br>- It reads and executes an SQL script to set up the database schema and initial data<br>- Upon successful execution, it reports the status and ensures cleanup by deleting the script itself, thereby playing a critical role in the project’s deployment and setup process.</td>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>app</b></summary>
				<blockquote>
					<details>
						<summary><b>forms</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/app/forms/home-forms.php'>home-forms.php</a></b></td>
								<td>- Handles form submissions for managing user statuses within the ChatGPT API project<br>- It facilitates both the deletion of existing statuses and the generation of new ones, ensuring that users adhere to API call limits<br>- By incorporating CSRF protection, it enhances security during these operations<br>- This functionality is integral to maintaining user interactions and data integrity across the application's status management features.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/info-forms.php'>info-forms.php</a></b></td>
								<td>- Handles user interactions for password changes and profile updates within the ChatGPT API project<br>- It ensures secure form submissions by validating CSRF tokens and checking input formats<br>- Upon successful validation, it updates the user’s password or profile information in the database, providing feedback on the success or failure of each operation, thereby enhancing user experience and security in account management.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/users-forms.php'>users-forms.php</a></b></td>
								<td>- User form submissions are processed to facilitate editing, deleting, and logging in as users within the ChatGPT API project<br>- Validation checks ensure data integrity and security against CSRF threats, while user management actions are performed utilizing a centralized UserHandler<br>- Successful operations not only handle user data updates but also manage user-specific resources, contributing to the overall functionality and user experience of the application.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/accounts-forms.php'>accounts-forms.php</a></b></td>
								<td>- Manages form submissions related to account editing and deletion within the ChatGPT API project<br>- It validates user inputs and handles both the update of existing accounts or the creation of new ones based on the provided data<br>- Additionally, it ensures secure operations through CSRF token checks and manages user feedback via session messages, enhancing overall user experience and data integrity.</td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>helpers</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/app/helpers/accounts-helper.php'>accounts-helper.php</a></b></td>
								<td>- Account-related helper functions enhance user interaction within the ChatGPT API project by generating dynamic HTML outputs<br>- They provide essential account details, dropdown options for days and cron settings, and a list of user accounts, enabling personalized management<br>- This functionality enriches the user experience by allowing seamless access and manipulation of account-related information.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/users-helper.php'>users-helper.php</a></b></td>
								<td>- Generate a dynamic user interface component that displays a list of users with relevant details for each, including username, API usage metrics, and expiration date<br>- It incorporates interactive elements for user management, such as update and delete options, and allows session-based administration actions<br>- This functionality facilitates user oversight within the ChatGPT API project, promoting effective management and interaction through an intuitive design.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/home-helper.php'>home-helper.php</a></b></td>
								<td>- Facilitates the generation of interactive HTML components for sharing and managing status updates on the home page of the ChatGPT API project<br>- It creates share and delete buttons, enhancing user engagement by allowing users to easily share content, copy text, and remove their posts<br>- By providing streamlined actions, it contributes to a more intuitive user experience within the overall application architecture.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/info-helper.php'>info-helper.php</a></b></td>
								<td>- Facilitates user profile management by providing helper functions that generate HTML data attributes and construct personalized system messages based on user information<br>- These functions enhance the user experience within the ChatGPT API by integrating user-specific data, allowing for dynamic and contextual interactions, which are essential for tailoring responses and functionalities in the overall codebase architecture.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/home-helper.php:Zone.Identifier'>home-helper.php:Zone.Identifier</a></b></td>
								<td>- Facilitating the transfer of zone-related metadata, the home-helper.php component assists in managing contextual data linked to user interactions within the application<br>- This enhances the overall architectural integrity by ensuring that relevant information about the user’s environment is accurately captured, thereby supporting features that rely on geographical or contextual awareness across the broader codebase.</td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>pages</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/app/pages/info.php'>info.php</a></b></td>
								<td>- Facilitates user interactions by enabling profile management and password changes within the ChatGPT API project<br>- It provides a user-friendly interface for updating personal information and securing accounts through password modifications<br>- Additionally, it enhances user experience by displaying system messages and integrating multimedia content, thereby positioning itself as an essential component of the overall application architecture.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/accounts.php'>accounts.php</a></b></td>
								<td>- Facilitates user account management and configuration settings within the ChatGPT API project<br>- It provides an interface for users to add or update their accounts on various social media platforms, customize prompts, and schedule posts<br>- Additionally, it displays a list of existing accounts, enhancing user experience and engagement by streamlining account-related operations.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/users.php'>users.php</a></b></td>
								<td>- User management functionality facilitates the addition and updating of user accounts within the ChatGPT API project<br>- It provides a user-friendly interface for inputting essential account details, such as usernames, password, and API usage limits, while also dynamically displaying a list of existing users<br>- This component enhances overall usability and control over user access and permissions in the application.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/home.php'>home.php</a></b></td>
								<td>- Displays user accounts and their corresponding statuses within the ChatGPT API project<br>- It retrieves user account information from the session, checks for existing accounts, and presents their statuses while allowing users to perform actions such as generating new statuses or viewing feeds<br>- The architecture facilitates interactive status management, enhancing user engagement through a dynamic interface that adapts based on available data.</td>
							</tr>
							</table>
						</blockquote>
					</details>
				</blockquote>
			</details>
			<details>
				<summary><b>lib</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='/root/lib/rss-lib.php'>rss-lib.php</a></b></td>
						<td>- Generates an RSS feed for user status updates, enabling users to subscribe and receive real-time notifications of new statuses<br>- It consolidates statuses from specified accounts or all accounts belonging to a user, sorting them by creation date<br>- By outputting the feed in XML format, it allows for easy integration with RSS readers and enhances user engagement through accessible updates.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/auth-lib.php'>auth-lib.php</a></b></td>
						<td>- User authentication and session management are facilitated through this component, which handles both login and logout processes<br>- It verifies user credentials and ensures secure session handling by managing session variables and addressing security concerns like session fixation and brute-force attacks<br>- This functionality is essential for maintaining user access control and protecting sensitive data within the ChatGPT API project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/status-lib.php'>status-lib.php</a></b></td>
						<td>- Generates social media status updates and accompanying images for user accounts within the ChatGPT API project<br>- By integrating user and account information, it produces dynamic posts tailored to various platforms, ensuring compliance with character limits and hashtag guidelines<br>- This functionality enhances user engagement through personalized content, making the application versatile in managing social media presence effectively.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/load-lib.php'>load-lib.php</a></b></td>
						<td>- Facilitates the loading of necessary helper, forms, and page files in the ChatGPT API project by verifying user session validity and permissions<br>- It ensures that only authenticated and authorized users can access specific resources while implementing security measures against blacklisted IP addresses and XSS attacks<br>- This file plays a critical role in maintaining the overall functionality and security of the codebase architecture.</td>
					</table>
				</blockquote>
			</details>
		</blockquote>
	</details>
</details>

---

## 🚀 Getting Started

### ☑️ Prerequisites

Before getting started with the installation, ensure your runtime environment meets the following requirements:

- **Web Server:** Apache
- **Programming Language:** PHP 8.0+
- **Database:** MySQL

### ⚙️ Installation

Install the project using the following steps:

1. **Upload Files:**

   - Upload all the project files to your hosting server.

2. **Set Webroot:**

   - Set the `public` folder as the webroot directory on your hosting server.

3. **Update Configuration:**

   - Open `root/config.php` and update the necessary variables, including MySQL database credentials.

4. **Install Database:**

   - Load the application in your web browser. Go to /install.php and the application will automatically install the database.

5. **Default Login:**

   - Use the default login credentials: `admin` for both username and password.

6. **Set Up Cron Jobs:**
   - Add the following cron jobs to automate tasks:
     ```sh
     /usr/bin/php /PATH-TO-CRON.PHP/cron.php reset_usage 0 12 1 * *
     /usr/bin/php /PATH-TO-CRON.PHP/cron.php clear_list 0 12 * * *
     /usr/bin/php /PATH-TO-CRON.PHP/cron.php run_status 0 * * * *
     /usr/bin/php /PATH-TO-CRON.PHP/cron.php cleanup 0 12 * * *
     ```
   - Replace `/PATH-TO-CRON.PHP/` with the actual path to your `cron.php` file.

### 🤖 Usage

Login as `admin` with the password `admin`. Follow these steps:

1. **Create or Change Users:**

   - Navigate to the **User** tab.
   - Add or update user details as needed.

2. **Create Status Campaigns:**
   - Go to the **Account** tab.
   - Click on **Add/Update New Account**.
   - Fill in the following details:
     - **Account Name**
     - **Platform**
     - **Prompt**
     - **Link**
     - **Image Instructions**
     - **Days**
     - **Post Schedule**
     - **Include Hashtags**
   - Click **Add/Update**.

Statuses are generated on schedule and added to the respective account feed and the user's collective omni feed. Use the feed with tools like IFTTT to update social media.

---

## Changelog

---

## 🎗 License

This project is protected under the [MIT License](https://github.com/djav1985/v-chatgpt-editor/blob/main/LICENSE) License.

---
