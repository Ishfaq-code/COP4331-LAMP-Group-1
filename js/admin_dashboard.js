import { clearCookie } from "./utility.js";

const usersById = new Map();
let messageTimeout;
let userStatusChart;
let userRolesChart;

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("logoutButton").addEventListener("click", () => {
        clearCookie();
        window.location.href = "index.html";
    });

    document.getElementById("searchUsersButton").addEventListener("click", fetchUsers);
    document.getElementById("searchContactsButton").addEventListener("click", fetchContacts);
    document.getElementById("createUserForm").addEventListener("submit", createUser);
    document.getElementById("suspendForm").addEventListener("submit", suspendUser);
    document.getElementById("resetPasswordForm").addEventListener("submit", resetPassword);

    document.querySelectorAll("[data-modal-close]").forEach(button => {
        button.addEventListener("click", () => closeAdminModal(button.dataset.modalClose));
    });

    document.getElementById("usersTableBody").addEventListener("click", event => {
        const button = event.target.closest("[data-user-action]");
        if (!button || button.disabled) return;

        if (button.dataset.userAction === "suspend") openSuspendModal(button.dataset.userId);
        if (button.dataset.userAction === "reset-password") openResetPasswordModal(button.dataset.userId);
    });

    fetchUsers();
    fetchContacts();
    fetchStats();
});

async function fetchStats() {
    try {
        const response = await fetch("api/admin/get_stats.php", {
            headers: getAuthHeaders()
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to fetch dashboard statistics");

        const stats = result.data;
        if (!stats || typeof stats !== "object") throw new Error("Dashboard statistics were unavailable.");

        document.getElementById("totalUsersStat").textContent = stats.totalUsers;
        document.getElementById("totalContactsStat").textContent = stats.totalContacts;
        document.getElementById("activeUsersStat").textContent = stats.activeUsers;
        document.getElementById("suspendedUsersStat").textContent = stats.suspendedUsers;
        document.getElementById("adminsStat").textContent = stats.admins;

        if (typeof window.Chart !== "function") throw new Error("Charts could not be loaded.");
        const chartTextColor = "#e0e0e0";
        const statusData = [Number(stats.activeUsers), Number(stats.suspendedUsers)];
        const roleData = [Number(stats.admins), Math.max(0, Number(stats.totalUsers) - Number(stats.admins))];

        if (userStatusChart) {
            userStatusChart.data.datasets[0].data = statusData;
            userStatusChart.update();
        } else {
            userStatusChart = new Chart(document.getElementById("userStatusChart"), {
                type: "doughnut",
                data: { labels: ["Active", "Suspended"], datasets: [{ data: statusData, backgroundColor: ["#d4af37", "#d9534f"], borderColor: "#101010", borderWidth: 3 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { color: chartTextColor, padding: 18 } } } }
            });
        }

        if (userRolesChart) {
            userRolesChart.data.datasets[0].data = roleData;
            userRolesChart.update();
        } else {
            userRolesChart = new Chart(document.getElementById("userRolesChart"), {
                type: "bar",
                data: { labels: ["Administrators", "Users"], datasets: [{ label: "Accounts", data: roleData, backgroundColor: ["#d4af37", "#9f7f19"], borderRadius: 7 }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { ticks: { color: chartTextColor }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: chartTextColor, precision: 0 }, grid: { color: "rgba(255,255,255,0.08)" } } }, plugins: { legend: { display: false } } }
            });
        }
    } catch (error) {
        showMessage(error.message, true);
    }
}

function showMessage(text, isError = false) {
    const msgBox = document.getElementById("statusMessage");
    msgBox.hidden = false;
    msgBox.className = `status-msg ${isError ? "status-error" : "status-success"}`;
    msgBox.textContent = text;
    clearTimeout(messageTimeout);
    messageTimeout = setTimeout(() => { msgBox.hidden = true; }, 5000);
}

function getAuthHeaders(includeJson = false) {
    const userId = document.cookie.match(/(?:^|,)\s*userId=(\d+)/)?.[1];
    if (!userId) {
        window.location.href = "index.html";
        throw new Error("Please log in again.");
    }

    return {
        ...(includeJson ? { "Content-Type": "application/json" } : {}),
        Authorization: `Bearer ${userId}`
    };
}

async function fetchUsers() {
    const search = document.getElementById("userSearchInput").value.trim();
    const roleFilter = document.getElementById("adminFilter").value;
    const statusFilter = document.getElementById("activeFilter").value;
    const params = new URLSearchParams();

    if (search) params.append("search", search);
    if (roleFilter !== "all") params.append("isAdmin", roleFilter === "admin" ? "true" : "false");
    if (statusFilter !== "all") params.append("isActive", statusFilter === "active" ? "true" : "false");

    try {
        const response = await fetch(`api/admin/get_users.php?${params.toString()}`, {
            headers: getAuthHeaders()
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to fetch users");

        const tbody = document.getElementById("usersTableBody");
        tbody.replaceChildren();
        usersById.clear();

        if (!Array.isArray(result.data) || result.data.length === 0) {
            tbody.innerHTML = "<tr><td colspan='7'>No users found.</td></tr>";
            return;
        }

        result.data.forEach(user => {
            usersById.set(String(user.id), user);
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${escapeHtml(user.id)}</td>
                <td>${escapeHtml(user.firstName)}</td>
                <td>${escapeHtml(user.lastName)}</td>
                <td>${escapeHtml(user.login)}</td>
                <td>${escapeHtml(user.role)}</td>
                <td><span class="badge ${user.active ? "badge-active" : "badge-inactive"}">${user.active ? "Active" : "Suspended"}</span></td>
                <td class="action-buttons-cell">
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm ${user.active ? "btn-danger" : "btn-outline-gold"}" data-user-action="suspend" data-user-id="${escapeHtml(user.id)}">${user.active ? "Suspend" : "Unsuspend"}</button>
                        <button type="button" class="btn btn-sm btn-outline-gold" data-user-action="reset-password" data-user-id="${escapeHtml(user.id)}">Reset Password</button>
                    </div>
                </td>`;
            tbody.appendChild(row);
        });
    } catch (error) {
        showMessage(error.message, true);
    }
}

async function fetchContacts() {
    const search = document.getElementById("contactSearchInput").value.trim();
    const userId = document.getElementById("userIdFilter").value.trim();
    const params = new URLSearchParams();

    if (search) params.append("search", search);
    if (userId) params.append("userId", userId);

    try {
        const response = await fetch(`api/admin/get_contacts.php?${params.toString()}`, {
            headers: getAuthHeaders()
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to fetch contacts");

        const tbody = document.getElementById("contactsTableBody");
        tbody.replaceChildren();
        if (!Array.isArray(result.data) || result.data.length === 0) {
            tbody.innerHTML = "<tr><td colspan='6'>No contacts found.</td></tr>";
            return;
        }

        result.data.forEach(contact => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${escapeHtml(contact.ID)}</td>
                <td>${escapeHtml(contact.FirstName || "")}</td>
                <td>${escapeHtml(contact.LastName || "")}</td>
                <td>${escapeHtml(contact.EmailAddress || "")}</td>
                <td>${escapeHtml(contact.PhoneNumber || "")}</td>
                <td>${escapeHtml(contact.UserID)}</td>`;
            tbody.appendChild(row);
        });
    } catch (error) {
        showMessage(error.message, true);
    }
}

async function createUser(event) {
    event.preventDefault();
    const payload = {
        firstName: document.getElementById("createFirstName").value.trim(),
        lastName: document.getElementById("createLastName").value.trim(),
        login: document.getElementById("createLogin").value.trim(),
        password: document.getElementById("createPassword").value,
        role: document.getElementById("createRole").value,
        active: document.getElementById("createActive").checked ? 1 : 0
    };

    try {
        const response = await fetch("api/admin/create_user.php", {
            method: "POST",
            headers: getAuthHeaders(true),
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to create user");

        showMessage("User created successfully!");
        document.getElementById("createUserForm").reset();
        await Promise.all([fetchUsers(), fetchStats()]);
    } catch (error) {
        showMessage(error.message, true);
    }
}

async function suspendUser(event) {
    event.preventDefault();
    const targetId = document.getElementById("suspendUserId").value;
    const targetUser = usersById.get(String(targetId));
    if (!targetUser) return;
    const newActiveStatus = Number(targetUser.active) === 1 ? 0 : 1;

    try {
        const response = await fetch("api/admin/update_user.php", {
            method: "PUT",
            headers: getAuthHeaders(true),
            body: JSON.stringify({
                id: targetUser.id,
                firstName: targetUser.firstName,
                lastName: targetUser.lastName,
                login: targetUser.login,
                role: targetUser.role,
                active: newActiveStatus
            })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to update account status");

        showMessage(`Account ${newActiveStatus ? "unsuspended" : "suspended"} successfully!`);
        closeAdminModal("suspendUserModal");
        await Promise.all([fetchUsers(), fetchStats()]);
    } catch (error) {
        showModalMessage("suspendActionMessage", error.message);
    }
}

async function resetPassword(event) {
    event.preventDefault();
    const targetId = document.getElementById("resetUserId").value;
    const targetUser = usersById.get(String(targetId));
    const newPassword = document.getElementById("newPasswordInput").value;
    if (!targetUser) return;

    try {
        const response = await fetch("api/admin/update_user.php", {
            method: "PUT",
            headers: getAuthHeaders(true),
            body: JSON.stringify({
                id: targetUser.id,
                firstName: targetUser.firstName,
                lastName: targetUser.lastName,
                login: targetUser.login,
                role: targetUser.role,
                active: targetUser.active,
                password: newPassword
            })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Failed to reset password");

        showMessage("Password updated successfully!");
        document.getElementById("resetPasswordForm").reset();
        closeAdminModal("resetPasswordModal");
    } catch (error) {
        showModalMessage("resetActionMessage", error.message);
    }
}

function formatUserInfo(user) {
    return `User #${user.id} · ${user.firstName} ${user.lastName} (${user.login})`;
}

function openSuspendModal(userId) {
    const user = usersById.get(String(userId));
    if (!user) return;
    document.getElementById("suspendUserId").value = user.id;
    document.getElementById("suspendUserInfo").textContent = formatUserInfo(user);
    const isActive = Boolean(Number(user.active));
    document.getElementById("statusModalTitle").textContent = isActive ? "Suspend Account" : "Unsuspend Account";
    document.getElementById("statusModalDescription").textContent = isActive
        ? "This account will no longer be able to log in."
        : "This account will be able to log in again.";
    const submitButton = document.getElementById("statusSubmitButton");
    submitButton.textContent = isActive ? "Suspend Account" : "Unsuspend Account";
    submitButton.classList.toggle("btn-danger", isActive);
    submitButton.classList.toggle("btn-outline-gold", !isActive);
    clearModalMessage("suspendActionMessage");
    document.getElementById("suspendUserModal").showModal();
}

function openResetPasswordModal(userId) {
    const user = usersById.get(String(userId));
    if (!user) return;
    document.getElementById("resetUserId").value = user.id;
    document.getElementById("resetUserInfo").textContent = formatUserInfo(user);
    clearModalMessage("resetActionMessage");
    document.getElementById("resetPasswordModal").showModal();
    document.getElementById("newPasswordInput").focus();
}

function closeAdminModal(modalId) {
    document.getElementById(modalId).close();
}

function showModalMessage(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.className = "status-msg status-error";
    element.hidden = false;
}

function clearModalMessage(elementId) {
    const element = document.getElementById(elementId);
    element.textContent = "";
    element.hidden = true;
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
