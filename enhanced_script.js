// Extension du script existant avec les nouvelles fonctionnalités

// Variables globales pour les nouvelles fonctionnalités
let searchTimeout
let notificationInterval
let autoSaveInterval
let switchConversation // Declare the switchConversation variable

// Initialisation des nouvelles fonctionnalités
document.addEventListener("DOMContentLoaded", () => {
  initializeSearch()
  initializeNotifications()
  initializeAutoSave()
  initializeFileUpload()
})

// === FONCTIONNALITÉ DE RECHERCHE ===
function initializeSearch() {
  // Ajouter la barre de recherche dans le header de la sidebar
  const sidebarHeader = document.querySelector(".sidebar-header")
  const searchContainer = document.createElement("div")
  searchContainer.className = "search-container"
  searchContainer.innerHTML = `
        <input type="text" id="search-input" placeholder="Rechercher..." class="search-input">
        <div id="search-results" class="search-results hidden"></div>
    `
  sidebarHeader.appendChild(searchContainer)

  const searchInput = document.getElementById("search-input")
  const searchResults = document.getElementById("search-results")

  searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout)
    const query = this.value.trim()

    if (query.length < 2) {
      searchResults.classList.add("hidden")
      return
    }

    searchTimeout = setTimeout(() => {
      performSearch(query)
    }, 300)
  })

  // Fermer les résultats quand on clique ailleurs
  document.addEventListener("click", (e) => {
    if (!searchContainer.contains(e.target)) {
      searchResults.classList.add("hidden")
    }
  })
}

async function performSearch(query) {
  try {
    const response = await fetch(`search.php?action=search_all&q=${encodeURIComponent(query)}`)
    const results = await response.json()
    displaySearchResults(results)
  } catch (error) {
    console.error("Erreur de recherche:", error)
  }
}

function displaySearchResults(results) {
  const searchResults = document.getElementById("search-results")
  let html = ""

  // Afficher les contacts
  if (results.contacts && results.contacts.length > 0) {
    html += '<div class="search-section"><h4>Contacts</h4>'
    results.contacts.forEach((contact) => {
      html += `
                <div class="search-item" onclick="selectContact('${contact.id}')">
                    <img src="${contact.photo}" alt="${contact.prenom}">
                    <span>${contact.prenom} ${contact.nom}</span>
                </div>
            `
    })
    html += "</div>"
  }

  // Afficher les groupes
  if (results.groups && results.groups.length > 0) {
    html += '<div class="search-section"><h4>Groupes</h4>'
    results.groups.forEach((group) => {
      html += `
                <div class="search-item" onclick="selectGroup('${group.id}')">
                    <img src="${group.photo_groupe}" alt="${group.nom_groupe}">
                    <span>${group.nom_groupe}</span>
                </div>
            `
    })
    html += "</div>"
  }

  // Afficher les messages
  if (results.messages && results.messages.length > 0) {
    html += '<div class="search-section"><h4>Messages</h4>'
    results.messages.slice(0, 5).forEach((message) => {
      const contextName = message.type === "message_contact" ? message.contact_nom : message.groupe_nom
      html += `
                <div class="search-item" onclick="goToMessage('${message.id}', '${message.type}')">
                    <div class="message-preview">
                        <strong>${contextName}</strong>
                        <p>${message.contenu.substring(0, 50)}...</p>
                        <small>${new Date(message.heure).toLocaleString()}</small>
                    </div>
                </div>
            `
    })
    html += "</div>"
  }

  if (html === "") {
    html = '<div class="no-results">Aucun résultat trouvé</div>'
  }

  searchResults.innerHTML = html
  searchResults.classList.remove("hidden")
}

function selectContact(contactId) {
  const chatItem = document.querySelector(`[data-user="contact-${contactId}"]`)
  if (chatItem) {
    chatItem.click()
  }
  document.getElementById("search-results").classList.add("hidden")
}

function selectGroup(groupId) {
  const chatItem = document.querySelector(`[data-user="groupe-${groupId}"]`)
  if (chatItem) {
    chatItem.click()
  }
  document.getElementById("search-results").classList.add("hidden")
}

// === NOTIFICATIONS EN TEMPS RÉEL ===
function initializeNotifications() {
  // Vérifier les mises à jour toutes les 5 secondes
  notificationInterval = setInterval(checkForNotifications, 5000)

  // Ajouter un indicateur de notifications
  const sidebarHeader = document.querySelector(".sidebar-header")
  const notificationBadge = document.createElement("div")
  notificationBadge.id = "notification-badge"
  notificationBadge.className = "notification-badge hidden"
  sidebarHeader.appendChild(notificationBadge)
}

async function checkForNotifications() {
  try {
    const response = await fetch("notifications.php?action=check_updates")
    const updates = await response.json()

    if (updates.length > 0) {
      showNotifications(updates)
      updateUnreadCount()
    }
  } catch (error) {
    console.error("Erreur de notification:", error)
  }
}

function showNotifications(updates) {
  updates.forEach((update) => {
    if (update.expediteur !== "1") {
      // Ne pas notifier ses propres messages
      showNotification(update)
    }
  })
}

function showNotification(update) {
  // Créer une notification toast
  const notification = document.createElement("div")
  notification.className = "toast-notification"

  const senderName = update.type === "contact" ? update.contact_nom : update.groupe_nom
  notification.innerHTML = `
        <div class="toast-header">
            <strong>${senderName}</strong>
            <small>${new Date(update.heure).toLocaleTimeString()}</small>
        </div>
        <div class="toast-body">${update.contenu}</div>
    `

  document.body.appendChild(notification)

  // Animation d'apparition
  setTimeout(() => notification.classList.add("show"), 100)

  // Supprimer après 5 secondes
  setTimeout(() => {
    notification.classList.remove("show")
    setTimeout(() => notification.remove(), 300)
  }, 5000)
}

async function updateUnreadCount() {
  try {
    const response = await fetch("notifications.php?action=unread_count&user_id=1")
    const data = await response.json()

    const badge = document.getElementById("notification-badge")
    if (data.count > 0) {
      badge.textContent = data.count
      badge.classList.remove("hidden")
    } else {
      badge.classList.add("hidden")
    }
  } catch (error) {
    console.error("Erreur compteur non lus:", error)
  }
}

// === GESTION DES FICHIERS JOINTS ===
function initializeFileUpload() {
  // Ajouter un bouton d'upload dans le footer du chat
  const chatFooter = document.querySelector(".chat-footer")
  const fileButton = document.createElement("button")
  fileButton.type = "button"
  fileButton.className = "file-button"
  fileButton.innerHTML = "📎"
  fileButton.onclick = openFileDialog

  const fileInput = document.createElement("input")
  fileInput.type = "file"
  fileInput.id = "file-input"
  fileInput.style.display = "none"
  fileInput.accept = "image/*,application/pdf,text/plain"
  fileInput.onchange = handleFileUpload

  chatFooter.insertBefore(fileButton, chatFooter.firstChild)
  chatFooter.appendChild(fileInput)
}

function openFileDialog() {
  document.getElementById("file-input").click()
}

async function handleFileUpload(event) {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append("attachment", file)
  formData.append("message_id", generateMessageId())

  try {
    const response = await fetch("file_manager.php", {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (result.success) {
      // Ajouter le fichier au message
      addFileToMessage(result)
    } else {
      alert("Erreur lors de l'upload: " + result.error)
    }
  } catch (error) {
    console.error("Erreur upload:", error)
    alert("Erreur lors de l'upload du fichier")
  }
}

function addFileToMessage(fileInfo) {
  const chatBody = document.querySelector(".chat-body")
  const fileMessage = document.createElement("div")
  fileMessage.className = "message sent file-message"

  const fileIcon = getFileIcon(fileInfo.type)
  fileMessage.innerHTML = `
        <div class="file-attachment">
            <span class="file-icon">${fileIcon}</span>
            <div class="file-info">
                <strong>${fileInfo.filename}</strong>
                <small>${formatFileSize(fileInfo.size)}</small>
            </div>
            <a href="${fileInfo.filepath}" download class="download-btn">⬇️</a>
        </div>
        <div class="message-footer">
            <span class="timestamp">${new Date().toLocaleTimeString()}</span>
            <span class="read-status">Envoyé</span>
        </div>
    `

  chatBody.appendChild(fileMessage)
  chatBody.scrollTop = chatBody.scrollHeight
}

function getFileIcon(mimeType) {
  if (mimeType.startsWith("image/")) return "🖼️"
  if (mimeType === "application/pdf") return "📄"
  if (mimeType.startsWith("text/")) return "📝"
  return "📎"
}

function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes"
  const k = 1024
  const sizes = ["Bytes", "KB", "MB", "GB"]
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
}

// === SAUVEGARDE AUTOMATIQUE ===
function initializeAutoSave() {
  // Sauvegarder toutes les 5 minutes
  autoSaveInterval = setInterval(performAutoSave, 300000)

  // Ajouter un indicateur de sauvegarde
  const indicator = document.createElement("div")
  indicator.id = "save-indicator"
  indicator.className = "save-indicator"
  indicator.textContent = "Sauvegardé"
  document.body.appendChild(indicator)
}

async function performAutoSave() {
  try {
    const response = await fetch("auto_save.php?action=create_backup")
    const result = await response.json()

    const indicator = document.getElementById("save-indicator")
    if (result.success) {
      indicator.textContent = "Sauvegardé"
      indicator.className = "save-indicator success"
    } else {
      indicator.textContent = "Erreur sauvegarde"
      indicator.className = "save-indicator error"
    }

    // Masquer l'indicateur après 2 secondes
    setTimeout(() => {
      indicator.className = "save-indicator"
    }, 2000)
  } catch (error) {
    console.error("Erreur sauvegarde automatique:", error)
  }
}

// === HISTORIQUE DES MODIFICATIONS ===
function logAction(action, details) {
  // Enregistrer l'action dans l'historique
  fetch("history.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=${encodeURIComponent(action)}&details=${encodeURIComponent(details)}`,
  }).catch((error) => {
    console.error("Erreur log historique:", error)
  })
}

// === FONCTIONS UTILITAIRES ===
function generateMessageId() {
  return "msg_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
}

function goToMessage(messageId, messageType) {
  // Logique pour naviguer vers un message spécifique
  console.log("Navigation vers message:", messageId, messageType)
  document.getElementById("search-results").classList.add("hidden")
}

// Nettoyer les intervalles quand la page se ferme
window.addEventListener("beforeunload", () => {
  if (notificationInterval) clearInterval(notificationInterval)
  if (autoSaveInterval) clearInterval(autoSaveInterval)
})

// Intégrer avec le script existant
const originalSwitchConversation = window.switchConversation // Assume switchConversation is a global function
window.switchConversation = (e) => {
  if (originalSwitchConversation) originalSwitchConversation(e)

  // Logger l'action
  const user = e.currentTarget.dataset.user
  const name = e.currentTarget.dataset.name
  logAction("switch_conversation", `Basculé vers ${name} (${user})`)
}
