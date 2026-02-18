<?php
session_start();
$db = new mysqli('localhost', 'root', '', 'task_management');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
    }
    $stmt->close();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle Super Admin: Add/Edit/Remove User
if (isset($_POST['add_user']) && $_SESSION['role'] == 'superadmin') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['edit_user']) && $_SESSION['role'] == 'superadmin') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $password, $role, $user_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['remove_user']) && $_SESSION['role'] == 'superadmin') {
    $user_id = $_POST['user_id'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle Admin: Create/Edit Task
if (isset($_POST['create_task']) && $_SESSION['role'] == 'admin') {
    $subject = $_POST['subject'];
    $description = $_POST['description'];
    $attachment = $_POST['attachment']; // Simulated file path
    $assigned_to = $_POST['assigned_to'];
    $created_by = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO tasks (subject, description, attachment, status, created_by, assigned_to) VALUES (?, ?, ?, 'pending', ?, ?)");
    $stmt->bind_param("sssii", $subject, $description, $attachment, $created_by, $assigned_to);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['edit_task']) && $_SESSION['role'] == 'admin') {
    $task_id = $_POST['task_id'];
    $subject = $_POST['subject'];
    $description = $_POST['description'];
    $attachment = $_POST['attachment'];
    $assigned_to = $_POST['assigned_to'];
    $stmt = $db->prepare("UPDATE tasks SET subject = ?, description = ?, attachment = ?, assigned_to = ? WHERE id = ?");
    $stmt->bind_param("sssii", $subject, $description, $attachment, $assigned_to, $task_id);
    $stmt->execute();
    $stmt->close();
}

// Handle User: Accept/Reject/Start/Stop/Complete Task
if (isset($_POST['accept_task']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("UPDATE tasks SET status = 'accepted' WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['reject_task']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("UPDATE tasks SET status = 'rejected' WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['start_task']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt = $db->prepare("INSERT INTO task_history (task_id, action) VALUES (?, 'start')");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['stop_task']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("INSERT INTO task_history (task_id, action) VALUES (?, 'stop')");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    // Calculate time spent
    $stmt = $db->prepare("SELECT action, action_time FROM task_history WHERE task_id = ? ORDER BY action_time");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $time_spent = 0;
    $start_time = null;
    while ($row = $result->fetch_assoc()) {
        if ($row['action'] == 'start') {
            $start_time = strtotime($row['action_time']);
        } elseif ($row['action'] == 'stop' && $start_time) {
            $time_spent += (strtotime($row['action_time']) - $start_time) / 3600; // Convert to hours
            $start_time = null;
        }
    }
    $stmt = $db->prepare("UPDATE tasks SET time_spent = ? WHERE id = ?");
    $stmt->bind_param("di", $time_spent, $task_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['complete_task']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("UPDATE tasks SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt = $db->prepare("INSERT INTO task_history (task_id, action) VALUES (?, 'complete')");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch data for AJAX
if (isset($_POST['get_users']) && in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    $result = $db->query("SELECT id, username, role FROM users");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
    exit();
}

if (isset($_POST['get_tasks'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    if ($role == 'admin') {
        $result = $db->query("SELECT t.*, u.username AS assigned_to FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.created_by = $user_id");
    } elseif ($role == 'user') {
        $result = $db->query("SELECT t.*, u.username AS created_by FROM tasks t LEFT JOIN users u ON t.created_by = u.id WHERE t.assigned_to = $user_id");
    }
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    echo json_encode($tasks);
    exit();
}

if (isset($_POST['get_task_history']) && $_SESSION['role'] == 'user') {
    $task_id = $_POST['task_id'];
    $stmt = $db->prepare("SELECT action, action_time FROM task_history WHERE task_id = ? ORDER BY action_time");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    echo json_encode($history);
    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 60px; }
        .sidebar { position: fixed; top: 56px; bottom: 0; left: 0; width: 250px; background: #f8f9fa; padding: 15px; }
        .main-content { margin-left: 270px; padding: 20px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1050; }
        @media (max-width: 768px) {
            .sidebar { position: static; width: 100%; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Task Management</a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="nav-link">Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="logout" class="btn btn-link nav-link">Logout</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php if (isset($_SESSION['role'])): ?>
        <div class="sidebar">
            <h4>Menu</h4>
            <ul class="nav flex-column">
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <li class="nav-item"><a class="nav-link" href="#user-management" onclick="showSection('user-management')">User Management</a></li>
                <?php elseif ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item"><a services
                    <li class="nav-link" href="#task-assignment" onclick="showSection('task-assignment')">Task Assignment</a></li>
                    <li class="nav-item"><a class="nav-link" href="#task-monitoring" onclick="showSection('task-monitoring')">Task Monitoring</a></li>
                <?php elseif ($_SESSION['role'] == 'user'): ?>
                    <li class="nav-item"><a class="nav-link" href="#task-management" onclick="showSection('task-management')">Task Management</a></li>
                    <li class="nav-item"><a class="nav-link" href="#task-tracking" onclick="showSection('task-tracking')">Task Tracking</a></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (!isset($_SESSION['username'])): ?>
            <!-- Login Form -->
            <div id="login-section">
                <h2>Login</h2>
                <form id="login-form" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Super Admin: User Management -->
            <?php if ($_SESSION['role'] == 'superadmin'): ?>
                <div id="user-management" class="section">
                    <h2>User Management</h2>
                    <form id="add-user-form" class="mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" class="form-control" id="add-username" placeholder="Username" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <input type="password" class="form-control" id="add-password" placeholder="Password" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <select class="form-select" id="add-role" required>
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success">Add User</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-bordered" id="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Admin: Task Assignment -->
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <div id="task-assignment" class="section" style="display: none;">
                    <h2>Task Assignment</h2>
                    <form id="create-task-form" class="mb-4">
                        <div class="mb-3">
                            <label for="task-subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="task-subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="task-description" class="form-label">Description</label>
                            <textarea class="form-control" id="task-description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="task-attachment" class="form-label">Attachment (File Path)</label>
                            <input type="text" class="form-control" id="task-attachment" placeholder="e.g., /files/doc.pdf">
                        </div>
                        <div class="mb-3">
                            <label for="task-assigned-to" class="form-label">Assign To</label>
                            <select class="form-select" id="task-assigned-to" required>
                                <option value="">Select User</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Task</button>
                    </form>
                    <table class="table table-bordered" id="task-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="task-monitoring" class="section" style="display: none;">
                    <h2>Task Monitoring</h2>
                    <table class="table table-bordered" id="monitoring-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Created At</th>
                                <th>Completed At</th>
                                <th>Time Spent (hrs)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- User: Task Management -->
            <?php if ($_SESSION['role'] == 'user'): ?>
                <div id="task-management" class="section">
                    <h2>Task Management</h2>
                    <table class="table table-bordered" id="user-task-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Attachment</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="task-tracking" class="section" style="display: none;">
                    <h2>Task Tracking</h2>
                    <div class="mb-3">
                        <label for="track-task-id" class="form-label">Select Task</label>
                        <select class="form-select" id="track-task-id">
                            <option value="">Select Task</option>
                        </select>
                    </div>
                    <table class="table table-bordered" id="task-history-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
        <div id="notification-toast" class="toast" role="alert" data-bs-autohide="true" data-bs-delay="3000">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            $('.section').hide();
            $('#' + sectionId).show();
        }

        function showToast(message) {
            $('#notification-toast .toast-body').text(message);
            const toast = new bootstrap.Toast($('#notification-toast'));
            toast.show();
        }

        $(document).ready(function() {
            <?php if (isset($_SESSION['role'])): ?>
                showSection('<?php echo $_SESSION['role'] == 'superadmin' ? 'user-management' : ($_SESSION['role'] == 'admin' ? 'task-assignment' : 'task-management'); ?>');
            <?php endif; ?>

            // Super Admin: Load Users
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'superadmin'): ?>
                function loadUsers() {
                    $.post('', { get_users: true }, function(data) {
                        const users = JSON.parse(data);
                        $('#user-table tbody').empty();
                        users.forEach(user => {
                            $('#user-table tbody').append(`
                                <tr>
                                    <td>${user.id}</td>
                                    <td>${user.username}</td>
                                    <td>${user.role}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-user" data-id="${user.id}" data-username="${user.username}" data-role="${user.role}">Edit</button>
                                        <button class="btn btn-sm btn-danger remove-user" data-id="${user.id}">Remove</button>
                                    </td>
                                </tr>
                            `);
                        });
                    });
                }
                loadUsers();

                $('#add-user-form').submit(function(e) {
                    e.preventDefault();
                    $.post('', {
                        add_user: true,
                        username: $('#add-username').val(),
                        password: $('#add-password').val(),
                        role: $('#add-role').val()
                    }, function() {
                        showToast('User added successfully');
                        loadUsers();
                        $('#add-user-form')[0].reset();
                    });
                });

                $(document).on('click', '.edit-user', function() {
                    const id = $(this).data('id');
                    const username = $(this).data('username');
                    const role = $(this).data('role');
                    const newUsername = prompt('Enter new username:', username);
                    const newPassword = prompt('Enter new password:');
                    const newRole = prompt('Enter new role (admin/user):', role);
                    if (newUsername && newPassword && newRole) {
                        $.post('', {
                            edit_user: true,
                            user_id: id,
                            username: newUsername,
                            password: newPassword,
                            role: newRole
                        }, function() {
                            showToast('User updated successfully');
                            loadUsers();
                        });
                    }
                });

                $(document).on('click', '.remove-user', function() {
                    if (confirm('Are you sure you want to remove this user?')) {
                        $.post('', { remove_user: true, user_id: $(this).data('id') }, function() {
                            showToast('User removed successfully');
                            loadUsers();
                        });
                    }
                });
            <?php endif; ?>

            // Admin: Load Users and Tasks
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                function loadUsersForAssignment() {
                    $.post('', { get_users: true }, function(data) {
                        const users = JSON.parse(data);
                        $('#task-assigned-to').empty().append('<option value="">Select User</option>');
                        users.forEach(user => {
                            if (user.role == 'user') {
                                $('#task-assigned-to').append(`<option value="${user.id}">${user.username}</option>`);
                            }
                        });
                    });
                }
                loadUsersForAssignment();

                function loadTasks() {
                    $.post('', { get_tasks: true }, function(data) {
                        const tasks = JSON.parse(data);
                        $('#task-table tbody').empty();
                        $('#monitoring-table tbody').empty();
                        tasks.forEach(task => {
                            $('#task-table tbody').append(`
                                <tr>
                                    <td>${task.id}</td>
                                    <td>${task.subject}</td>
                                    <td>${task.status}</td>
                                    <td>${task.assigned_to}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-task" data-id="${task.id}" data-subject="${task.subject}" data-description="${task.description}" data-attachment="${task.attachment}" data-assigned_to="${task.assigned_to}">Edit</button>
                                    </td>
                                </tr>
                            `);
                            $('#monitoring-table tbody').append(`
                                <tr>
                                    <td>${task.id}</td>
                                    <td>${task.subject}</td>
                                    <td>${task.status}</td>
                                    <td>${task.assigned_to}</td>
                                    <td>${task.created_at}</td>
                                    <td>${task.completed_at || '-'}</td>
                                    <td>${task.time_spent || 0}</td>
                                </tr>
                            `);
                        });
                    });
                }
                loadTasks();

                $('#create-task-form').submit(function(e) {
                    e.preventDefault();
                    $.post('', {
                        create_task: true,
                        subject: $('#task-subject').val(),
                        description: $('#task-description').val(),
                        attachment: $('#task-attachment').val(),
                        assigned_to: $('#task-assigned-to').val()
                    }, function() {
                        showToast('Task created and assigned successfully');
                        loadTasks();
                        $('#create-task-form')[0].reset();
                    });
                });

                $(document).on('click', '.edit-task', function() {
                    const id = $(this).data('id');
                    const subject = $(this).data('subject');
                    const description = $(this).data('description');
                    const attachment = $(this).data('attachment');
                    const assigned_to = $(this).data('assigned_to');
                    const newSubject = prompt('Enter new subject:', subject);
                    const newDescription = prompt('Enter new description:', description);
                    const newAttachment = prompt('Enter new attachment path:', attachment);
                    const newAssignedTo = prompt('Enter new assigned user ID:', assigned_to);
                    if (newSubject && newDescription && newAssignedTo) {
                        $.post('', {
                            edit_task: true,
                            task_id: id,
                            subject: newSubject,
                            description: newDescription,
                            attachment: newAttachment,
                            assigned_to: newAssignedTo
                        }, function() {
                            showToast('Task updated successfully');
                            loadTasks();
                        });
                    }
                });
            <?php endif; ?>

            // User: Load Tasks and History
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'user'): ?>
                function loadUserTasks() {
                    $.post('', { get_tasks: true }, function(data) {
                        const tasks = JSON.parse(data);
                        $('#user-task-table tbody').empty();
                        $('#track-task-id').empty().append('<option value="">Select Task</option>');
                        tasks.forEach(task => {
                            let actions = '';
                            if (task.status == 'pending') {
                                actions = `
                                    <button class="btn btn-sm btn-success accept-task" data-id="${task.id}">Accept</button>
                                    <button class="btn btn-sm btn-danger reject-task" data-id="${task.id}">Reject</button>
                                `;
                            } else if (task.status == 'accepted' || task.status == 'in_progress') {
                                actions = `
                                    <button class="btn btn-sm btn-primary start-task" data-id="${task.id}" ${task.status == 'in_progress' ? 'disabled' : ''}>Start</button>
                                    <button class="btn btn-sm btn-warning stop-task" data-id="${task.id}" ${task.status != 'in_progress' ? 'disabled' : ''}>Stop</button>
                                    <button class="btn btn-sm btn-success complete-task" data-id="${task.id}">Complete</button>
                                `;
                            }
                            $('#user-task-table tbody').append(`
                                <tr>
                                    <td>${task.id}</td>
                                    <td>${task.subject}</td>
                                    <td>${task.description}</td>
                                    <td>${task.attachment || '-'}</td>
                                    <td>${task.status}</td>
                                    <td>${task.created_by}</td>
                                    <td>${actions}</td>
                                </tr>
                            `);
                            $('#track-task-id').append(`<option value="${task.id}">${task.subject}</option>`);
                        });
                    });
                }
                loadUserTasks();

                $(document).on('click', '.accept-task', function() {
                    $.post('', { accept_task: true, task_id: $(this).data('id') }, function() {
                        showToast('Task accepted');
                        loadUserTasks();
                    });
                });

                $(document).on('click', '.reject-task', function() {
                    $.post('', { reject_task: true, task_id: $(this).data('id') }, function() {
                        showToast('Task rejected');
                        loadUserTasks();
                    });
                });

                $(document).on('click', '.start-task', function() {
                    $.post('', { start_task: true, task_id: $(this).data('id') }, function() {
                        showToast('Task started');
                        loadUserTasks();
                    });
                });

                $(document).on('click', '.stop-task', function() {
                    $.post('', { stop_task: true, task_id: $(this).data('id') }, function() {
                        showToast('Task stopped');
                        loadUserTasks();
                    });
                });

                $(document).on('click', '.complete-task', function() {
                    $.post('', { complete_task: true, task_id: $(this).data('id') }, function() {
                        showToast('Task completed');
                        loadUserTasks();
                    });
                });

                $('#track-task-id').change(function() {
                    const taskId = $(this).val();
                    if (taskId) {
                        $.post('', { get_task_history: true, task_id: taskId }, function(data) {
                            const history = JSON.parse(data);
                            $('#task-history-table tbody').empty();
                            history.forEach(entry => {
                                $('#task-history-table tbody').append(`
                                    <tr>
                                        <td>${entry.action}</td>
                                        <td>${entry.action_time}</td>
                                    </tr>
                                `);
                            });
                        });
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>