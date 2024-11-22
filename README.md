<p align="center">
    <img src=".png" align="center" width="30%">
</p>
<p align="center"><h1 align="center"><code>❯ REPLACE-ME</code></h1></p>
<p align="center">
	<em>Empowering Your API Experience, Effortlessly!</em>
</p>
<p align="center">
	<!-- local repository, no metadata badges. --></p>
<p align="center">Built with the tools and technologies:</p>
<p align="center">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
</p>
<br>

<details><summary>Table of Contents</summary>

- [📍 Overview](#-overview)
- [👾 Features](#-features)
- [📁 Project Structure](#-project-structure)
	- [📂 Project Index](#-project-index)
- [🚀 Getting Started](#-getting-started)
	- [☑️ Prerequisites](#️-prerequisites)
	- [⚙️ Installation](#️-installation)
	- [🤖 Usage](#-usage)
- [🎗 License](#-license)

</details>
<hr>

## 📍 Overview

The ChatGPT API Status Generator addresses the need for efficient social media management by automating status updates and user account management. Key features include user-friendly interfaces for account creation, status generation, and secure password management. Ideal for social media managers and developers, it streamlines content sharing and enhances user engagement.

---

## 👾 Features

|      | Feature         | Summary       |
| :--- | :---:           | :---          |
| ⚙️  | **Architecture**  | <ul><li>Built primarily in `<PHP>` with a structured file organization for easy navigation.</li><li>Utilizes a modular approach with separate files for configuration, database management, and user interactions.</li><li>Incorporates a cron job for scheduled tasks, enhancing operational efficiency.</li></ul> |
| 🔩 | **Code Quality**  | <ul><li>Follows best practices for code organization, ensuring maintainability and readability.</li><li>Includes CSRF protection and input validation to enhance security.</li><li>Utilizes structured queries for database interactions, minimizing the risk of SQL injection.</li></ul> |
| 📄 | **Documentation** | <ul><li>Documentation is primarily in `<PHP>` with clear comments explaining the functionality of each component.</li><li>Includes usage instructions and configuration settings for the ChatGPT API.</li><li>Provides detailed descriptions of file contents, enhancing developer understanding.</li></ul> |
| 🔌 | **Integrations**  | <ul><li>Integrates with the OpenAI API for generating responses and managing user interactions.</li><li>Supports RSS feed generation for user status updates, enhancing content distribution.</li><li>Facilitates social media account management through API interactions.</li></ul> |
| 🧩 | **Modularity**    | <ul><li>Modular design allows for easy updates and feature additions without affecting the entire codebase.</li><li>Components are organized into helpers, forms, and pages, promoting separation of concerns.</li><li>Encourages reusability of code through helper functions for common tasks.</li></ul> |
| 🧪 | **Testing**       | <ul><li>Includes mechanisms for validating user input and ensuring data integrity.</li><li>Automated tasks in the cron job enhance reliability and reduce manual errors.</li><li>Testing commands are not explicitly defined, but the structure supports unit testing practices.</li></ul> |
| ⚡️  | **Performance**   | <ul><li>Utilizes caching strategies via `.htaccess` for improved load times and reduced server load.</li><li>Efficient database interactions through structured queries enhance response times.</li><li>Scheduled tasks optimize resource usage, ensuring smooth operation during peak times.</li></ul> |
| 🛡️ | **Security**      | <ul><li>Implements CSRF protection and session management to secure user interactions.</li><li>Utilizes password hashing for secure user authentication.</li><li>Prevents unauthorized access to sensitive directories through script termination.</li></ul> |
| 📦 | **Dependencies**  | <ul><li>Relies on `<PHP>` as the primary programming language.</li><li>Utilizes `.htaccess` for URL rewriting and caching strategies.</li><li>Includes `robots.txt` for managing web crawling and optimizing server load.</li></ul> |

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
    │       │   ├── admin
    │       │   │   ├── dasdasdas
    │       │   │   │   └── index.php
    │       │   │   └── tesstfsdfsdf
    │       │   │       ├── 6722a3c3c03ad.png
    │       │   │       ├── 6722ab0482aca.png
    │       │   │       ├── 6722ab723237e.png
    │       │   │       ├── 6722abb9cb8c5.png
    │       │   │       ├── 6722ac1d0b8c2.png
    │       │   │       └── index.php
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
				<td>- Configuration settings establish essential parameters for the ChatGPT API Status Generator, including API keys, endpoints, model preferences, and database connection details<br>- These settings facilitate seamless interaction with the OpenAI API, guiding the AI's responses and managing system behavior<br>- By defining operational limits and system prompts, it ensures the application functions effectively within its intended scope, enhancing user experience and performance.</td>
			</tr>
			<tr>
				<td><b><a href='/root/cron.php'>cron.php</a></b></td>
				<td>- Handles scheduled tasks for the ChatGPT API project, including resetting API usage, updating statuses, clearing the IP blacklist, and purging outdated images<br>- It ensures efficient management of user accounts by automating routine maintenance, thereby enhancing system performance and user experience<br>- This functionality is crucial for maintaining operational integrity and optimizing resource usage within the overall codebase architecture.</td>
			</tr>
			<tr>
				<td><b><a href='/root/db.php'>db.php</a></b></td>
				<td>- Database connection and initialization are established through a class that manages interactions with the database<br>- It ensures the creation of essential tables for user management, account details, and logging activities<br>- Additionally, it handles installation checks and updates, facilitating smooth transitions between application versions while maintaining data integrity and security through password hashing and structured queries.</td>
			</tr>
			</table>
			<details>
				<summary><b>app</b></summary>
				<blockquote>
					<details>
						<summary><b>forms</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/app/forms/accounts-forms.php'>accounts-forms.php</a></b></td>
								<td>- Account management functionality is provided, enabling users to create, edit, and delete accounts within the ChatGPT API project<br>- It ensures data validation and security through CSRF token checks, while also managing associated status updates in the database<br>- Successful operations lead to user feedback and redirection, enhancing the overall user experience in managing their accounts effectively.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/home-forms.php'>home-forms.php</a></b></td>
								<td>- Handles user interactions for generating and deleting status updates within the ChatGPT API project<br>- It validates CSRF tokens to ensure secure requests, manages API call limits for users, and facilitates the deletion of associated images when a status is removed<br>- This functionality is crucial for maintaining user-generated content and ensuring a seamless experience in the application’s status management system.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/info-forms.php'>info-forms.php</a></b></td>
								<td>- Facilitates user password management within the ChatGPT API project by handling password change requests<br>- It ensures security through CSRF token validation and password strength checks, while also providing user feedback on errors<br>- Upon successful validation, it updates the user's password in the database, enhancing overall user account security and experience in the application.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/forms/users-forms.php'>users-forms.php</a></b></td>
								<td>- User management functionality is facilitated through this component, enabling the creation, modification, and deletion of user accounts within the ChatGPT API project<br>- It ensures secure handling of user data, including password validation and CSRF protection, while also managing user roles and associated resources<br>- This integration plays a crucial role in maintaining the integrity and security of user interactions within the overall application architecture.</td>
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
								<td>- Account details generation facilitates the retrieval and display of user-specific information, including account limits and usage statistics, enhancing user experience within the ChatGPT API project<br>- It also supports the creation of dropdown options for scheduling tasks and managing user accounts, thereby streamlining account management and interaction<br>- This functionality is integral to the overall architecture, ensuring users can efficiently monitor and control their API usage.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/home-helper.php'>home-helper.php</a></b></td>
								<td>- Generates a set of interactive share buttons for status updates within the ChatGPT API project<br>- It facilitates users in sharing status text and associated images, while also providing options to copy text, download images, and delete statuses<br>- This functionality enhances user engagement and streamlines the management of status updates, contributing to a more dynamic and user-friendly experience in the application.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/helpers/users-helper.php'>users-helper.php</a></b></td>
								<td>- Generates a comprehensive user list for the ChatGPT API, displaying essential user data and providing interactive options for updating, deleting, and logging in as different users<br>- This functionality enhances user management within the application, facilitating administrative tasks and improving overall user experience by allowing seamless interaction with user accounts.</td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>pages</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/app/pages/accounts.php'>accounts.php</a></b></td>
								<td>- Facilitates the management of social media accounts by providing a user-friendly interface for adding and updating account information<br>- It allows users to input account details, select platforms, schedule posts, and include multimedia instructions<br>- Additionally, it displays existing account information and error messages, enhancing the overall user experience within the ChatGPT API project.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/home.php'>home.php</a></b></td>
								<td>- Generates and displays the status of user accounts within the ChatGPT API project<br>- It retrieves account information and associated statuses, allowing users to view, generate, and share statuses seamlessly<br>- The interface supports account management by providing options to collapse or expand status details, enhancing user interaction while ensuring a streamlined experience in monitoring and sharing account activities.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/info.php'>info.php</a></b></td>
								<td>- Facilitates user password management within the ChatGPT API project by providing a secure interface for changing passwords<br>- It ensures user authentication through session management and CSRF protection, enhancing overall security<br>- This component plays a crucial role in maintaining user account integrity and contributes to the project's focus on user experience and security.</td>
							</tr>
							<tr>
								<td><b><a href='/root/app/pages/users.php'>users.php</a></b></td>
								<td>- User management functionality is facilitated through a form that allows for the addition and updating of user details, including username, password, account limits, and expiration dates<br>- It integrates with the overall architecture by providing a user interface for managing API access, ensuring that administrators can efficiently handle user accounts while displaying current user data dynamically<br>- This enhances the project's capability to manage user interactions with the ChatGPT API effectively.</td>
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
						<td><b><a href='/root/lib/auth-lib.php'>auth-lib.php</a></b></td>
						<td>- Authentication logic facilitates user login and logout processes within the ChatGPT API project<br>- It manages session states, verifies user credentials, and ensures security through session regeneration and CSRF token generation<br>- Additionally, it handles failed login attempts by monitoring IP addresses, enhancing overall security<br>- This component is essential for maintaining user access control and protecting sensitive information throughout the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/common-lib.php'>common-lib.php</a></b></td>
						<td>- Utility functions enhance the ChatGPT API by ensuring secure user input handling, managing session messages, and overseeing IP blacklist operations<br>- They facilitate user and account information retrieval, status update management, and API usage tracking<br>- Additionally, these functions support external API communication, contributing to the overall architecture's robustness and security, while enabling efficient interaction with user data and system resources.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/load-lib.php'>load-lib.php</a></b></td>
						<td>- Facilitates user access control and page loading within the ChatGPT API project<br>- It verifies user authentication and checks for blacklisted IP addresses, ensuring secure access<br>- By dynamically including relevant helper and forms files based on user permissions, it streamlines the management of user-specific content while maintaining session integrity and preventing unauthorized access to sensitive areas of the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/rss-lib.php'>rss-lib.php</a></b></td>
						<td>- Generates an RSS feed for user status updates, allowing users to access and subscribe to updates from specific accounts or all accounts owned by a user<br>- By retrieving and organizing status information from the database, it constructs a well-formed RSS XML output, enhancing user engagement and providing a streamlined way to follow updates within the ChatGPT API project.</td>
					</tr>
					<tr>
						<td><b><a href='/root/lib/status-lib.php'>status-lib.php</a></b></td>
						<td>- Generate and manage social media statuses through the ChatGPT API by retrieving account information, creating compelling content, and generating associated images<br>- This functionality enhances user engagement by automating status updates tailored to specific platforms, while also incorporating relevant hashtags<br>- The process includes error handling and retries for image generation, ensuring a seamless experience for users in managing their social media presence.</td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>public</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='/root/public/.htaccess'>.htaccess</a></b></td>
						<td>- Facilitates URL rewriting and caching for a web application, enhancing user experience and performance<br>- It serves a default image when specific PNG files are missing, redirects root and index requests to the home page, and internally rewrites certain paths to a central index file<br>- Additionally, it manages cache control for various file types, optimizing resource delivery and reducing server load.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/feeds.php'>feeds.php</a></b></td>
						<td>- Generates an RSS feed for user accounts within the ChatGPT API project, facilitating content distribution and updates<br>- By validating and sanitizing input parameters, it ensures secure access to user-specific data<br>- This functionality enhances user engagement by allowing seamless integration of personalized content into various feed readers, thereby enriching the overall user experience within the application ecosystem.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/index.php'>index.php</a></b></td>
						<td>- Serves as the main dashboard for the ChatGPT API Status Generator, acting as the entry point for the admin interface<br>- It facilitates user interaction by providing navigation tabs for various sections, including statuses, accounts, and user management, while dynamically loading content based on user roles<br>- Additionally, it incorporates session management and integrates essential scripts and styles for a cohesive user experience.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/login.php'>login.php</a></b></td>
						<td>- Facilitates user authentication for the ChatGPT API project by providing a login interface for administrators<br>- It establishes a session, integrates necessary configurations and libraries, and presents a form for username and password input<br>- Additionally, it handles error messages, ensuring a smooth user experience while managing access to the administrative features of the application.</td>
					</tr>
					<tr>
						<td><b><a href='/root/public/robots.txt'>robots.txt</a></b></td>
						<td>- Facilitates web crawling management by defining rules for search engine bots<br>- The robots.txt located in the public directory specifies a crawl delay, ensuring that bots access the site at a controlled pace<br>- This contributes to the overall architecture by optimizing server load and enhancing user experience, while also guiding search engines on how to interact with the site's content.</td>
					</tr>
					</table>
					<details>
						<summary><b>images</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='/root/public/images/index.php'>index.php</a></b></td>
								<td>- Prevents direct access to the images directory by terminating script execution<br>- This mechanism enhances security within the project structure, ensuring that sensitive files are not exposed to unauthorized users<br>- By controlling access at this level, the overall integrity of the application is maintained, contributing to a robust architecture that prioritizes data protection and user privacy.</td>
							</tr>
							</table>
							<details>
								<summary><b>admin</b></summary>
								<blockquote>
									<details>
										<summary><b>dasdasdas</b></summary>
										<blockquote>
											<table>
											<tr>
												<td><b><a href='/root/public/images/admin/dasdasdas/index.php'>index.php</a></b></td>
												<td>- Prevents direct access to the admin image directory by terminating execution when accessed<br>- This mechanism enhances security within the project structure, ensuring that sensitive files are protected from unauthorized viewing or manipulation<br>- By implementing this safeguard, the overall integrity of the application is maintained, contributing to a robust and secure codebase architecture.</td>
											</tr>
											</table>
										</blockquote>
									</details>
									<details>
										<summary><b>tesstfsdfsdf</b></summary>
										<blockquote>
											<table>
											<tr>
												<td><b><a href='/root/public/images/admin/tesstfsdfsdf/index.php'>index.php</a></b></td>
												<td>- Prevents unauthorized access to the directory by terminating script execution<br>- Positioned within the public images folder for admin, it serves as a security measure to safeguard sensitive content from being directly accessed or executed<br>- This contributes to the overall architecture by enhancing the project's security posture, ensuring that only intended functionalities are accessible to users.</td>
											</tr>
											</table>
										</blockquote>
									</details>
								</blockquote>
							</details>
						</blockquote>
					</details>
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

---

## 🎗 License

This project is protected under the [MIT License](https://github.com/djav1985/v-chatgpt-editor/blob/main/LICENSE) License.

---
