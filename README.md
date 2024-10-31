<div align="left" style="position: relative;">
<img src=".png" align="right" width="30%" style="margin: -20px 0 0 20px;">
<h1><code>❯ REPLACE-ME</code></h1>
<p align="left">
	<em>Empower Your Status, Simplify Your Sharing!</em>
</p>
<p align="left">
	<!-- local repository, no metadata badges. --></p>
<p align="left">Built with the tools and technologies:</p>
<p align="left">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
</p>
</div>
<br clear="right">

## 🔗 Table of Contents

- [� Table of Contents](#-table-of-contents)
- [📍 Overview](#-overview)
- [👾 Features](#-features)
- [📁 Project Structure](#-project-structure)
	- [📂 Project Index](#-project-index)
- [🚀 Getting Started](#-getting-started)
	- [☑️ Prerequisites](#️-prerequisites)
	- [⚙️ Installation](#️-installation)
	- [🤖 Usage](#-usage)
- [📌 Project Roadmap](#-project-roadmap)
- [🎗 License](#-license)

---

## 📍 Overview

The ChatGPT API Status Generator addresses the need for seamless social media management by automating status updates across platforms. Key features include user-friendly dashboards, secure account management, and automated scheduling. Ideal for social media managers and businesses, it enhances engagement and efficiency, simplifying the process of maintaining an active online presence.

---

## 👾 Features

|      | Feature         | Summary       |
| :--- | :---:           | :---          |
| ⚙️  | **Architecture**  | <ul><li>Built primarily in `<PHP>` with a focus on modular design.</li><li>Utilizes a structured approach for configuration management through `root/config.php`.</li><li>Incorporates a database management layer via `root/db.php` for data persistence and structured interactions.</li></ul> |
| 🔩 | **Code Quality**  | <ul><li>Follows best practices for secure coding, including CSRF protection in forms.</li><li>Utilizes clear naming conventions and organized file structure for maintainability.</li><li>Includes utility functions in `root/lib/common-lib.php` to enhance code reusability.</li></ul> |
| 📄 | **Documentation** | <ul><li>Documentation is primarily in `<PHP>` with a focus on user guides and API integration.</li><li>Code comments and structured file organization enhance understanding of functionality.</li><li>Limited external documentation; could benefit from a dedicated README or wiki.</li></ul> |
| 🔌 | **Integrations**  | <ul><li>Integrates with the `<ChatGPT API>` for generating social media statuses.</li><li>Supports RSS feed generation for user updates via `root/lib/rss-lib.php`.</li><li>Handles user authentication and session management through `root/lib/auth-lib.php`.</li></ul> |
| 🧩 | **Modularity**    | <ul><li>Modular design allows for easy updates and maintenance of individual components.</li><li>Separation of concerns with distinct files for user management, account handling, and status updates.</li><li>Utilizes helper functions to streamline operations across different modules.</li></ul> |
| 🧪 | **Testing**       | <ul><li>Testing commands are not explicitly defined; consider implementing unit tests for critical components.</li><li>Encourages validation checks in forms to ensure data integrity.</li><li>Automated tasks handled in `root/cron.php` can be monitored for performance and reliability.</li></ul> |
| ⚡️  | **Performance**   | <ul><li>Optimizes performance through caching mechanisms in `root/public/.htaccess`.</li><li>Scheduled tasks in `root/cron.php` help maintain optimal resource management.</li><li>Efficient database interactions ensure quick data retrieval and updates.</li></ul> |
| 🛡️ | **Security**      | <ul><li>Implements user authentication and session management to secure access.</li><li>Utilizes CSRF tokens in forms to prevent cross-site request forgery.</li><li>Includes IP blacklisting in `root/lib/auth-lib.php` to enhance security against unauthorized access.</li></ul> |
| 📦 | **Dependencies**  | <ul><li>Minimal dependencies, primarily relying on `<PHP>` and configuration files like `robots.txt` and `.htaccess`.</li><li>Ensures compatibility with web servers through proper configuration.</li><li>Lightweight structure allows for easy deployment and management.</li></ul> |

---

## 📁 Project Structure

```sh
└── /
    ├── CHANGELOG.md
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
    │   │   │   ├── home-helper.php
    │   │   │   └── users-helper.php
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
    │       │   │   ├── index.php
    │       │   │   ├── login.css
    │       │   │   ├── mobile.css
    │       │   │   ├── pages.css
    │       │   │   └── styles.css
    │       │   ├── images
    │       │   │   ├── background-marble.png
    │       │   │   ├── default.jpg
    │       │   │   ├── default.png
    │       │   │   ├── index.php
    │       │   │   └── logo.png
    │       │   ├── index.php
    │       │   └── js
    │       │       ├── footer-scripts.js
    │       │       ├── header-scripts.js
    │       │       └── index.php
    │       ├── favicon.ico
    │       ├── feeds.php
    │       ├── images
    │       │   └── index.php
    │       ├── index.php
    │       ├── login.php
    │       └── robots.txt
    └── v-chatgpt-social-status-feeds.png
```


### 📂 Project Index
<details open>
	<summary><b><code>/</code></b></summary>
	<details> <!-- __root__ Submodule -->
		<summary><b>__root__</b></summary>
		<blockquote>
			<table>
			</table>
		</blockquote>
	</details>
	<details> <!-- root Submodule -->
		<summary><b>root</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='/root/config.php'>config.php</a></b></td>
				<td>- Configuration settings are defined for the ChatGPT API Status Generator, encompassing essential parameters such as API keys, endpoints, model preferences, and database connection details<br>- These settings facilitate the integration and functionality of the application, ensuring proper communication with the OpenAI API while managing system behavior and data handling, ultimately supporting the generation of social media status updates.</td>
			</tr>
			<tr>
				<td><b><a href='/root/db.php'>db.php</a></b></td>
				<td>- Database management is established through a script that facilitates connection setup and table initialization for the ChatGPT API project<br>- It ensures the creation of essential tables for user management, account details, and logging, while also handling installation checks and version updates<br>- This foundational component supports the overall architecture by enabling data persistence and structured interactions within the application.</td>
			</tr>
			<tr>
				<td><b><a href='/root/cron.php'>cron.php</a></b></td>
				<td>- Handles scheduled tasks within the ChatGPT API project, ensuring efficient management of resources and user accounts<br>- It resets API usage, updates statuses, clears IP blacklists, and purges outdated images<br>- By automating these processes, it maintains optimal performance and compliance with usage limits, contributing to the overall stability and reliability of the application.</td>
			</tr>
			</table>
			<details>
				<summary><b>public</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='/root/public/index.php'>index.php</a></b></td>
						<td>- Serves as the main dashboard for the ChatGPT API Status Generator, functioning as the entry point for the admin interface<br>- It facilitates user interactions by providing navigation tabs for various sections, including statuses, accounts, and user management, while dynamically loading content based on user roles<br>- Additionally, it integrates essential scripts and styles for a cohesive user experience, ensuring a responsive and interactive dashboard.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/.htaccess'>.htaccess</a></b></td>
						<td>- Facilitates URL rewriting and caching for a web application, enhancing user experience and performance<br>- It serves a default image when specific PNG files are missing, redirects certain requests to a home page, and internally rewrites paths for seamless navigation<br>- Additionally, it manages cache control for various file types, optimizing load times and resource management across the project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/login.php'>login.php</a></b></td>
						<td>- Facilitates user authentication for the ChatGPT API project by providing a login interface for administrators<br>- It establishes a session, includes necessary configuration and library files, and renders a form for username and password input<br>- Additionally, it displays error messages related to login attempts, ensuring secure access to the admin functionalities of the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/robots.txt'>robots.txt</a></b></td>
						<td>- Facilitates web crawling management by specifying directives for search engine bots<br>- The inclusion of a crawl-delay parameter helps control the frequency of requests made by crawlers, ensuring optimal server performance and resource allocation<br>- This contributes to the overall architecture by enhancing site visibility while maintaining stability, aligning with the project's goal of efficient web presence management.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/feeds.php'>feeds.php</a></b></td>
						<td>- Generates an RSS feed for the ChatGPT API tailored to specific user accounts<br>- By validating and sanitizing input parameters, it ensures secure access to personalized content<br>- This functionality enhances user engagement by providing a streamlined way for users to receive updates related to their accounts, thereby integrating seamlessly into the overall architecture of the project.</td>
					</tr>
					</table>
					<details>
						<summary><b>images</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/public/images/index.php'>index.php</a></b></td>
								<td>- Prevents direct access to the images directory by terminating script execution<br>- This mechanism enhances security within the project structure, ensuring that sensitive files are not exposed to unauthorized users<br>- By controlling access at this level, the architecture maintains integrity and confidentiality of the resources stored in the public images directory, contributing to the overall robustness of the application.</td>
							</tr>
							</table>
						</blockquote>
					</details>
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
								<td>- Handles the generation and deletion of status updates for users interacting with the ChatGPT API<br>- It validates user actions through CSRF protection, manages API call limits, and updates the database accordingly<br>- Additionally, it ensures the removal of associated image files when a status is deleted, maintaining the integrity of user data and enhancing the overall user experience within the application.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/info-forms.php'>info-forms.php</a></b></td>
								<td>- Facilitates user password updates within the ChatGPT API project by handling form submissions securely<br>- It validates input, checks for CSRF tokens, ensures password compliance, and updates the user's password in the database after hashing<br>- Success and error messages are managed through session variables, enhancing user experience and security in the application’s authentication process.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/users-forms.php'>users-forms.php</a></b></td>
								<td>- User management functionality is facilitated through the handling of user creation, modification, and deletion within the ChatGPT API project<br>- It ensures secure input validation, password management, and session handling while providing feedback to users<br>- Additionally, it supports administrative actions, such as logging in as another user, thereby enhancing the overall user experience and system integrity within the application architecture.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/accounts-forms.php'>accounts-forms.php</a></b></td>
								<td>- Account management functionality is provided, enabling users to create, edit, and delete accounts associated with the ChatGPT API<br>- It ensures data integrity through validation checks and handles user input securely, including CSRF protection<br>- Additionally, it manages database interactions for storing account details and related statuses, facilitating a seamless user experience within the broader project architecture.</td>
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
								<td>- Generates user account details and management options for a ChatGPT API application<br>- It retrieves and displays essential account information, including usage statistics and expiration dates, while also providing dropdown options for scheduling tasks<br>- Additionally, it facilitates the display and management of multiple user accounts, allowing for updates and deletions, thereby enhancing user experience and interaction within the overall application architecture.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/users-helper.php'>users-helper.php</a></b></td>
								<td>- Generates a comprehensive user list for the ChatGPT API, displaying essential user data attributes and providing interactive options for updating, deleting, or logging in as a user<br>- This functionality enhances user management within the application, facilitating administrative tasks and improving overall user experience by presenting relevant information in a structured HTML format.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/home-helper.php'>home-helper.php</a></b></td>
								<td>- Generates a set of interactive share buttons for status updates within the ChatGPT API project<br>- These buttons facilitate sharing text and images, as well as deleting statuses, enhancing user engagement and functionality<br>- By providing a seamless interface for sharing and managing content, it contributes to the overall user experience and interactivity of the application.</td>
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
								<td>- Facilitates user password management within the ChatGPT API project by providing a secure interface for changing passwords<br>- It ensures user authentication through session management and CSRF protection, enhancing overall security<br>- This component plays a crucial role in maintaining user account integrity and contributes to the user experience by allowing seamless password updates.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/accounts.php'>accounts.php</a></b></td>
								<td>- Facilitates the management of user accounts within the ChatGPT API project by providing a user-friendly interface for adding and updating account information<br>- It allows users to specify account details, select social media platforms, and schedule posts, while ensuring security through CSRF tokens<br>- Additionally, it displays existing account information and error messages, enhancing the overall user experience in account management.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/users.php'>users.php</a></b></td>
								<td>- Facilitates user management within the ChatGPT API project by providing a user interface for adding and updating user details<br>- It allows administrators to input essential information such as username, password, account limits, and API call settings<br>- Additionally, it dynamically generates a list of existing users, enhancing the overall user administration experience while ensuring secure interactions through CSRF protection.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/home.php'>home.php</a></b></td>
								<td>- Generates a user-friendly interface for managing and displaying the status of ChatGPT API accounts<br>- It retrieves user accounts, displays their statuses, and allows users to generate new statuses or view feeds<br>- The interactive elements enhance user engagement by enabling status sharing and toggling visibility of status details, contributing to a cohesive experience within the overall project architecture focused on API management and user interaction.</td>
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
						<td>- Generates an RSS feed for user status updates, allowing users to access and subscribe to updates from specific accounts or all accounts owned by a user<br>- By retrieving and organizing status information from the database, it constructs a well-formed RSS XML output, enhancing user engagement and providing a streamlined way to follow updates within the ChatGPT API project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/auth-lib.php'>auth-lib.php</a></b></td>
						<td>- Authentication logic facilitates user login and logout processes within the ChatGPT API project<br>- It manages session states, verifies user credentials, and implements security measures such as session regeneration and CSRF token generation<br>- Additionally, it handles failed login attempts and blacklists IP addresses to enhance security, ensuring a robust and secure user experience across the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/common-lib.php'>common-lib.php</a></b></td>
						<td>- Utility functions enhance the ChatGPT API project by ensuring secure user input handling, managing session messages, and overseeing IP blacklist operations<br>- They facilitate user and account information retrieval, status updates, and API usage tracking, contributing to a robust architecture that prioritizes security and efficient data management<br>- Overall, these functions support the seamless operation and integrity of the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/status-lib.php'>status-lib.php</a></b></td>
						<td>- Generates dynamic status updates for various social media platforms by retrieving account information and utilizing an API to create engaging content<br>- It also creates associated images based on the generated status, appends relevant hashtags, and manages token usage and costs in the database<br>- This functionality enhances user engagement and streamlines content creation within the ChatGPT API project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/load-lib.php'>load-lib.php</a></b></td>
						<td>- Facilitates user access control and page loading for the ChatGPT API project by verifying user authentication and IP address status<br>- It ensures that only authorized users can access specific pages and includes necessary helper and forms files based on user permissions<br>- This mechanism enhances security and user experience within the overall architecture of the application.</td>
					</tr>
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
- **Programming Language:** PHP 7.4+
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
	- Load the application in your web browser. The application will automatically install the database.

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

Run using the following command:
❯ echo 'INSERT-RUN-COMMAND-HERE'


---
## 📌 Project Roadmap

- [X] **`Task 1`**: <strike>Implement feature one.</strike>
- [ ] **`Task 2`**: Implement feature two.
- [ ] **`Task 3`**: Implement feature three.


---
## 🎗 License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT). For more details, refer to the [LICENSE](./LICENSE) file.

---
