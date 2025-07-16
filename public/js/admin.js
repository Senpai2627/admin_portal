// Global variables
let authToken = localStorage.getItem('authToken');
let currentUser = null;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    if (!authToken) {
        showLoginModal();
    } else {
        checkAuthAndLoadApp();
    }
});

// Authentication functions
function showLoginModal() {
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
}

function checkAuthAndLoadApp() {
    fetch('/api/auth/me', {
        headers: {
            'Authorization': `Bearer ${authToken}`
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Authentication failed');
        }
        return response.json();
    })
    .then(data => {
        currentUser = data.user;
        document.getElementById('currentUser').textContent = `${currentUser.first_name} ${currentUser.last_name}`;
        document.getElementById('userAvatar').textContent = currentUser.first_name.charAt(0).toUpperCase();
        loadApp();
    })
    .catch(error => {
        console.error('Auth error:', error);
        localStorage.removeItem('authToken');
        showLoginModal();
    });
}

function loadApp() {
    document.getElementById('mainApp').style.display = 'block';
    loadDashboard();
    setupNavigation();
}

// Login form handler
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            authToken = data.token;
            localStorage.setItem('authToken', authToken);
            currentUser = data.user;
            
            // Hide login modal
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            
            // Show main app
            loadApp();
        } else {
            showError('loginError', data.error || 'Login failed');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        showError('loginError', 'Network error. Please try again.');
    });
});

// Navigation setup
function setupNavigation() {
    document.querySelectorAll('[data-section]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            showSection(section);
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(navLink => {
                navLink.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
}

function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    document.getElementById(sectionName).style.display = 'block';
    
    // Load section data
    switch(sectionName) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'users':
            loadUsers();
            break;
        case 'roles':
            loadRoles();
            break;
        case 'permissions':
            loadPermissions();
            break;
    }
}

// Dashboard functions
function loadDashboard() {
    // Load dashboard statistics
    Promise.all([
        fetch('/api/users', { headers: { 'Authorization': `Bearer ${authToken}` } }),
        fetch('/api/roles', { headers: { 'Authorization': `Bearer ${authToken}` } }),
        fetch('/api/permissions', { headers: { 'Authorization': `Bearer ${authToken}` } })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([usersData, rolesData, permissionsData]) => {
        if (usersData.success) {
            document.getElementById('totalUsers').textContent = usersData.users.length;
            document.getElementById('activeUsers').textContent = 
                usersData.users.filter(u => u.status === 'active').length;
        }
        if (rolesData.success) {
            document.getElementById('totalRoles').textContent = rolesData.roles.length;
        }
        if (permissionsData.success) {
            document.getElementById('totalPermissions').textContent = permissionsData.permissions.length;
        }
    })
    .catch(error => {
        console.error('Dashboard load error:', error);
    });
}

// User management functions
function loadUsers() {
    fetch('/api/users', {
        headers: {
            'Authorization': `Bearer ${authToken}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUsers(data.users);
        } else {
            showError('usersError', data.error || 'Failed to load users');
        }
    })
    .catch(error => {
        console.error('Users load error:', error);
        showError('usersError', 'Network error loading users');
    });
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTable');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.first_name} ${user.last_name}</td>
            <td>${user.roles || 'None'}</td>
            <td>
                <span class="badge bg-${user.status === 'active' ? 'success' : 'secondary'}">
                    ${user.status}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showCreateUserModal() {
    const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
    modal.show();
}

function createUser() {
    const userData = {
        username: document.getElementById('newUsername').value,
        email: document.getElementById('newEmail').value,
        password: document.getElementById('newPassword').value,
        first_name: document.getElementById('newFirstName').value,
        last_name: document.getElementById('newLastName').value,
        status: document.getElementById('newStatus').value
    };
    
    fetch('/api/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
            loadUsers();
            showSuccess('User created successfully!');
        } else {
            showError('createUserError', data.error || 'Failed to create user');
        }
    })
    .catch(error => {
        console.error('Create user error:', error);
        showError('createUserError', 'Network error creating user');
    });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch(`/api/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadUsers();
                showSuccess('User deleted successfully!');
            } else {
                showError('usersError', data.error || 'Failed to delete user');
            }
        })
        .catch(error => {
            console.error('Delete user error:', error);
            showError('usersError', 'Network error deleting user');
        });
    }
}

// Role management functions
function loadRoles() {
    fetch('/api/roles', {
        headers: {
            'Authorization': `Bearer ${authToken}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRoles(data.roles);
        } else {
            showError('rolesError', data.error || 'Failed to load roles');
        }
    })
    .catch(error => {
        console.error('Roles load error:', error);
        showError('rolesError', 'Network error loading roles');
    });
}

function displayRoles(roles) {
    const tbody = document.getElementById('rolesTable');
    tbody.innerHTML = '';
    
    roles.forEach(role => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${role.id}</td>
            <td>${role.name}</td>
            <td>${role.description}</td>
            <td>${role.level}</td>
            <td>${role.permissions || 'None'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editRole(${role.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteRole(${role.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showCreateRoleModal() {
    const modal = new bootstrap.Modal(document.getElementById('createRoleModal'));
    modal.show();
}

function createRole() {
    const roleData = {
        name: document.getElementById('newRoleName').value,
        description: document.getElementById('newRoleDescription').value,
        level: parseInt(document.getElementById('newRoleLevel').value)
    };
    
    fetch('/api/roles', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(roleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createRoleModal')).hide();
            loadRoles();
            showSuccess('Role created successfully!');
        } else {
            showError('createRoleError', data.error || 'Failed to create role');
        }
    })
    .catch(error => {
        console.error('Create role error:', error);
        showError('createRoleError', 'Network error creating role');
    });
}

function deleteRole(roleId) {
    if (confirm('Are you sure you want to delete this role?')) {
        fetch(`/api/roles/${roleId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadRoles();
                showSuccess('Role deleted successfully!');
            } else {
                showError('rolesError', data.error || 'Failed to delete role');
            }
        })
        .catch(error => {
            console.error('Delete role error:', error);
            showError('rolesError', 'Network error deleting role');
        });
    }
}

// Permission management functions
function loadPermissions() {
    fetch('/api/permissions', {
        headers: {
            'Authorization': `Bearer ${authToken}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPermissions(data.permissions);
        } else {
            showError('permissionsError', data.error || 'Failed to load permissions');
        }
    })
    .catch(error => {
        console.error('Permissions load error:', error);
        showError('permissionsError', 'Network error loading permissions');
    });
}

function displayPermissions(permissions) {
    const tbody = document.getElementById('permissionsTable');
    tbody.innerHTML = '';
    
    permissions.forEach(permission => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${permission.id}</td>
            <td>${permission.name}</td>
            <td>${permission.description}</td>
            <td>${permission.resource}</td>
            <td>
                <span class="badge bg-info">${permission.action}</span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editPermission(${permission.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePermission(${permission.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showCreatePermissionModal() {
    const modal = new bootstrap.Modal(document.getElementById('createPermissionModal'));
    modal.show();
}

function createPermission() {
    const permissionData = {
        name: document.getElementById('newPermissionName').value,
        description: document.getElementById('newPermissionDescription').value,
        resource: document.getElementById('newPermissionResource').value,
        action: document.getElementById('newPermissionAction').value
    };
    
    fetch('/api/permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(permissionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createPermissionModal')).hide();
            loadPermissions();
            showSuccess('Permission created successfully!');
        } else {
            showError('createPermissionError', data.error || 'Failed to create permission');
        }
    })
    .catch(error => {
        console.error('Create permission error:', error);
        showError('createPermissionError', 'Network error creating permission');
    });
}

function deletePermission(permissionId) {
    if (confirm('Are you sure you want to delete this permission?')) {
        fetch(`/api/permissions/${permissionId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadPermissions();
                showSuccess('Permission deleted successfully!');
            } else {
                showError('permissionsError', data.error || 'Failed to delete permission');
            }
        })
        .catch(error => {
            console.error('Delete permission error:', error);
            showError('permissionsError', 'Network error deleting permission');
        });
    }
}

// Utility functions
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    } else {
        alert('Error: ' + message);
    }
}

function showSuccess(message) {
    // Create and show success toast or alert
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert-success');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}

function logout() {
    fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${authToken}`
        }
    })
    .then(() => {
        localStorage.removeItem('authToken');
        authToken = null;
        currentUser = null;
        document.getElementById('mainApp').style.display = 'none';
        showLoginModal();
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Force logout anyway
        localStorage.removeItem('authToken');
        location.reload();
    });
}

// Placeholder functions for edit operations
function editUser(userId) {
    alert('Edit user functionality to be implemented');
}

function editRole(roleId) {
    alert('Edit role functionality to be implemented');
}

function editPermission(permissionId) {
    alert('Edit permission functionality to be implemented');
}