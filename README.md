# TaskEase - Task Management System

TaskEase is a lightweight, web-based Task Management System designed to streamline workflow, task assignment, and time tracking. It provides a simple yet effective interface for managing users and tasks with role-based access control.

## 🚀 Features

- **Role-Based Access Control (RBAC):**
  - **Superadmin:** Full control over user management (Create, Edit, Remove users).
  - **Admin:** Can create tasks, assign them to users, and monitor overall progress.
  - **User:** Can manage assigned tasks (Accept, Reject, Start, Stop, Complete) and track time spent.
- **Task Management:** Create tasks with subjects, descriptions, and attachments.
- **Time Tracking:** Start and stop timers on tasks to automatically calculate total hours spent.
- **Real-time Notifications:** Toast-style notifications for actions like task creation, user updates, etc.
- **Responsive Design:** Built with Bootstrap 5, ensuring usability across desktops and mobile devices.

## 🛠️ Tech Stack

- **Backend:** PHP
- **Frontend:** HTML5, CSS3 (Bootstrap 5), JavaScript (jQuery)
- **Database:** MySQL
- **Libraries:**
  - [Bootstrap 5](https://getbootstrap.com/)
  - [jQuery](https://jquery.com/)

## 📋 Database Setup

1.  **Create Database:** Create a new MySQL database named `task_management`.
2.  **Import SQL:** Import the `task_management.sql` file provided in the repository into your database.
3.  **Connection Settings:** Open `index.php` and ensure the database connection parameters (host, username, password, database) match your local environment:
    ```php
    $db = new mysqli('localhost', 'root', '', 'task_management');
    ```

## 🪜 Getting Started

1.  Clone the repository to your local server (e.g., XAMPP `htdocs` or WAMP `www` folder).
2.  Start your Apache and MySQL servers.
3.  Navigate to the project directory in your browser (e.g., `http://localhost/TaskEase`).

### Default Credentials

| Role | Username | Password |
| :--- | :--- | :--- |
| **Superadmin** | `superadmin` | `admin123` |
| **Admin** | `admin` | `admin123` |
| **User** | `user1` | `user123` |

## 📖 Usage Guide

### Superadmin
- Manage the users of the system.
- Change roles or passwords as needed.

### Admin
- Create new tasks and assign them to specific team members.
- Attach file paths or documentation to tasks.
- Monitor the status and time spent on all tasks in the "Task Monitoring" section.

### User
- View all pending tasks in the "Task Management" section.
- **Accept/Reject:** Decide whether to take on a new task.
- **Start/Stop:** Use the built-in timer to track work hours.
- **Complete:** Mark a task as finished to log the completion timestamp.
- **Task Tracking:** View your action history (start/stop times) for any task.

---
*Created with ❤️ for efficient task management.*
