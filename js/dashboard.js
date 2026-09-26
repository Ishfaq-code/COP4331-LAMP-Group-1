import { clearCookie, readCookie } from "./utility.js"

let alertTimeout
let pendingDeleteId = null
let editingContactId = null
const contactCache = new Map()

document.addEventListener("DOMContentLoaded", async function () {
    const user = readCookie()
    await refreshContacts()

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal')) {
            window.closeModal(event.target.id)
        }

        const deleteButton = event.target.closest('.delete-contact')
        if (deleteButton) {
            window.promptDeleteContact(
                deleteButton.dataset.contactId,
                deleteButton.dataset.contactName
            )
        }

        const editButton = event.target.closest('.edit-contact')
        if (editButton) {
            window.openEditContact(editButton.dataset.contactId)
        }
    })

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(function (modal) {
                window.closeModal(modal.id)
            })
        }
    })
})

window.openModal = function (id) {
    const modal = document.getElementById(id)

    if (!modal) {
        return
    }

    modal.classList.add('show')
    document.body.style.overflow = 'hidden'
}

window.closeModal = function (id) {
    const modal = document.getElementById(id)

    if (!modal) {
        return
    }

    modal.classList.remove('show')
    document.body.style.overflow = ''
}

window.doLogout = function () {
    clearCookie()
    window.location.href = 'index.html'
}

async function fetchUsers(userId, searchTerm = '') {
    const alertBox = document.getElementById('alertMessage')
    const params = new URLSearchParams({ search: searchTerm })

    alertBox.style.display = 'none'

    try {
        const response = await fetch(`api/get_contacts.php?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${userId}`
            }
        })
        const data = await response.json()

        if (response.status === 200) {
            renderContacts(Array.isArray(data.data) ? data.data : [])
            showAlert(alertBox, 'alert-success', data.message || 'Contacts retrieved successfully.')
            return data
        }

        renderContacts([])
        showAlert(alertBox, 'alert-error', data.message || 'Unable to retrieve contacts.')
        return data
    } catch (error) {
        renderContacts([])
        showAlert(alertBox, 'alert-error', 'Network error or server unavailable.')
        return { statusCode: 500, message: 'Network error or server unavailable.', data: [] }
    }
}

async function refreshContacts() {
    const user = readCookie()
    const searchTerm = document.getElementById('searchText').value.trim()
    return fetchUsers(user.userId, searchTerm)
}

function renderContacts(contacts) {
    const listContainer = document.getElementById('contactList')
    listContainer.innerHTML = ''
    contactCache.clear()

    if (contacts.length === 0) {
        listContainer.innerHTML = '<p class="empty-message">No contacts found.</p>'
        return
    }

    contacts.forEach(function (contact) {
        contactCache.set(String(contact.ID), contact)
        const firstName = contact.FirstName || ''
        const lastName = contact.LastName || ''
        const email = contact.EmailAddress || 'No email provided'
        const phone = contact.PhoneNumber || 'No phone number provided'
        const fullName = `${firstName} ${lastName}`.trim()
        const circle = document.createElement('div')

        circle.className = 'contact-circle'
        circle.innerHTML = `
            <div class="contact-actions">
                <button
                    type="button"
                    class="edit-contact"
                    title="Edit Contact"
                    aria-label="Edit ${escapeHtml(fullName)}"
                    data-contact-id="${escapeHtml(contact.ID)}"
                >
                    Edit
                </button>
                <button
                    type="button"
                    class="delete-contact"
                    title="Delete Contact"
                    aria-label="Delete ${escapeHtml(fullName)}"
                    data-contact-id="${escapeHtml(contact.ID)}"
                    data-contact-name="${escapeHtml(fullName)}"
                >
                    ×
                </button>
            </div>
            <div class="contact-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <strong class="contact-first-name">${escapeHtml(firstName)}</strong>
            <strong class="contact-last-name">${escapeHtml(lastName)}</strong>
            <span class="contact-email">${escapeHtml(email)}</span>
            <span class="contact-phone">${escapeHtml(phone)}</span>
        `
        listContainer.appendChild(circle)
    })
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
}

async function addContact(userId, firstName, lastName, email, phoneNumber) {
    const result = await contactRequest(
        'api/add_contact.php',
        'POST',
        userId,
        { firstName, lastName, email, phone: phoneNumber },
        201
    )

    if (result.statusCode === 201) {
        document.getElementById('contactFirstNameInput').value = ''
        document.getElementById('contactLastNameInput').value = ''
        document.getElementById('contactEmailInput').value = ''
        document.getElementById('contactPhoneInput').value = ''
        window.closeModal('addContactModal')
        await refreshContacts()
    }

    return result
}

async function deleteContact(userId, contactId) {
    const result = await contactRequest(
        'api/delete_contact.php',
        'DELETE',
        userId,
        { id: contactId },
        200
    )

    if (result.statusCode === 200) {
        await refreshContacts()
    }

    return result
}

async function updateContact(userId, contactId, firstName, lastName, email, phoneNumber) {
    const result = await contactRequest(
        'api/update_contact.php',
        'PUT',
        userId,
        {
            ID: contactId,
            FirstName: firstName,
            LastName: lastName,
            EmailAddress: email,
            PhoneNumber: phoneNumber
        },
        200
    )

    if (result.statusCode === 200) {
        await refreshContacts()
    }

    return result
}

async function contactRequest(endpoint, method, userId, body, expectedStatus) {
    const alertBox = document.getElementById('alertMessage')

    try {
        const response = await fetch(endpoint, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${userId}`
            },
            body: JSON.stringify(body)
        })
        const data = await response.json()

        if (response.status === expectedStatus) {
            showAlert(alertBox, 'alert-success', data.message || 'Contact request succeeded.')
        } else {
            showAlert(alertBox, 'alert-error', data.message || 'Contact request failed.')
        }

        return data
    } catch (error) {
        const result = {
            statusCode: 500,
            message: 'Network error or server unavailable.',
            data: null
        }
        showAlert(alertBox, 'alert-error', result.message)
        return result
    }
}

window.addContactMock = async function () {
    const user = readCookie()
    const firstName = document.getElementById('contactFirstNameInput').value.trim()
    const lastName = document.getElementById('contactLastNameInput').value.trim()
    const email = document.getElementById('contactEmailInput').value.trim()
    const phoneNumber = document.getElementById('contactPhoneInput').value.trim()

    if (!firstName || !lastName || !email || !phoneNumber) {
        const message = document.getElementById('addContactMsg')
        message.textContent = 'Please complete all contact fields.'
        return { statusCode: 400, message: message.textContent, data: null }
    }

    document.getElementById('addContactMsg').textContent = ''
    return addContact(user.userId, firstName, lastName, email, phoneNumber)
}

window.searchContacts = refreshContacts

window.promptDeleteContact = function (contactId, contactName) {
    pendingDeleteId = contactId
    document.getElementById('deleteTargetName').textContent = contactName
    window.openModal('deleteConfirmModal')
}

window.openEditContact = function (contactId) {
    const contact = contactCache.get(String(contactId))

    if (!contact) {
        return
    }

    editingContactId = contactId
    document.getElementById('editFirstNameInput').value = contact.FirstName || ''
    document.getElementById('editLastNameInput').value = contact.LastName || ''
    document.getElementById('editEmailInput').value = contact.EmailAddress || ''
    document.getElementById('editPhoneInput').value = contact.PhoneNumber || ''
    document.getElementById('editContactMsg').textContent = ''
    window.openModal('editContactModal')
}

window.saveEditedContact = async function () {
    if (editingContactId === null) {
        return
    }

    const firstName = document.getElementById('editFirstNameInput').value.trim()
    const lastName = document.getElementById('editLastNameInput').value.trim()
    const email = document.getElementById('editEmailInput').value.trim()
    const phoneNumber = document.getElementById('editPhoneInput').value.trim()
    const message = document.getElementById('editContactMsg')

    if (!firstName || !lastName || !email || !phoneNumber) {
        message.textContent = 'Please complete all contact fields.'
        return
    }

    message.textContent = ''
    const user = readCookie()
    const result = await updateContact(
        user.userId,
        editingContactId,
        firstName,
        lastName,
        email,
        phoneNumber
    )

    if (result.statusCode === 200) {
        editingContactId = null
        window.closeModal('editContactModal')
    }
}

window.confirmDeleteContact = async function () {
    if (pendingDeleteId === null) {
        return
    }

    const user = readCookie()
    const result = await deleteContact(user.userId, pendingDeleteId)

    if (result.statusCode === 200) {
        pendingDeleteId = null
        window.closeModal('deleteConfirmModal')
    }
}

window.addContact = addContact
window.deleteContact = deleteContact
window.updateContact = updateContact

function showAlert(alertBox, alertClass, message) {
    clearTimeout(alertTimeout)
    alertBox.className = `alert ${alertClass}`
    alertBox.textContent = message
    alertBox.style.display = 'block'

    alertTimeout = setTimeout(function () {
        alertBox.style.display = 'none'
    }, 4000)
}
