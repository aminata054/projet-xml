const chatItems = document.querySelectorAll(".chat-item");
const chatHeader = document.querySelector(".chat-header");
const chatBody = document.querySelector(".chat-body");
const form = document.querySelector(".chat-footer");
const destinataireInput = form.querySelector("input[name='destinataire']");
const groupeInput = form.querySelector("input[name='groupe']");
const expediteurInput = form.querySelector("input[name='expediteur']");
const dynamicSettings = document.querySelector("#dynamic-settings");
const settingsLink = document.querySelector("#settings-link");

if (!window.messages || !window.contacts || !window.groupes || !window.allContacts) {
    console.error("Les objets 'messages', 'contacts', 'groupes' ou 'allContacts' ne sont pas définis.");
    if (dynamicSettings) {
        dynamicSettings.innerHTML = '<h3>Erreur</h3><p>Données non disponibles.</p>';
    }
}

function createMessageHTML(msg, isGroup) {
    const readStatus = msg.statut === "double check" ? "Lu" : "Non lu";
    const senderName = isGroup && msg.senderName ? `<div class="sender-name">${msg.senderName}</div>` : "";
    return `
        <div class="message ${msg.sender}">
            ${senderName}
            <div class="text">${msg.text}</div>
            <div class="message-footer">
                <span class="timestamp">${new Date(msg.time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</span>
                <span class="read-status">${readStatus}</span>
            </div>
        </div>
    `;
}

function createContactSettings(contact, contactId) {
    console.log('Affichage du formulaire de contact:', contact);
    return `
        <h3>Modifier le profil de ${contact.prenom} ${contact.nom}</h3>
        <form action="add.php" method="post" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label for="contact-prenom-${contactId}">Prénom :</label>
                <input type="text" name="prenom" id="contact-prenom-${contactId}" class="form-input" value="${contact.prenom}" required>
            </div>
            <div class="form-group">
                <label for="contact-nom-${contactId}">Nom :</label>
                <input type="text" name="nom" id="contact-nom-${contactId}" class="form-input" value="${contact.nom}" required>
            </div>
            <div class="form-group">
                <label for="contact-numero-${contactId}">Numéro de téléphone :</label>
                <input type="text" name="numero_telephone" id="contact-numero-${contactId}" class="form-input" value="${contact.numero === 'Non défini' ? '' : contact.numero}">
            </div>
            <div class="form-group">
                <label for="contact-photo-${contactId}">Photo de profil :</label>
                <input type="file" name="photo" id="contact-photo-${contactId}" class="form-file" accept="image/*">
                <img src="${contact.photo_profile}" alt="${contact.prenom} ${contact.nom}" class="profile-img">
            </div>
            <div class="form-group">
                <label for="contact-status-${contactId}">Statut :</label>
                <input type="text" name="status" id="contact-status-${contactId}" class="form-input" value="${contact.status === 'Non défini' ? '' : contact.status}">
            </div>
            <input type="hidden" name="contact_id" value="${contactId}">
            <button type="submit" name="update_contact" class="sidebar-button">Mettre à jour</button>
        </form>
    `;
}

function createGroupSettings(groupe, groupeId) {
    console.log('Affichage du formulaire de groupe:', groupe);
    const membresList = groupe.membres.length > 0
        ? groupe.membres.map((membre, index) => `
            <li class="contact-item">
                ${membre}
                <button type="button" class="remove-member" data-member-id="${groupe.membreIds[index]}">Supprimer</button>
            </li>
        `).join('')
        : '<li class="contact-item">Aucun membre</li>';
    const contactOptions = Object.entries(window.allContacts).map(([id, name]) =>
        `<option value="${id}">${name}</option>`
    ).join('');
    return `
        <h3>Modifier le groupe : ${groupe.nom_groupe}</h3>
        <form action="add.php" method="post" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label for="groupe-nom-${groupeId}">Nom du groupe :</label>
                <input type="text" name="nom_groupe" id="groupe-nom-${groupeId}" class="form-input" value="${groupe.nom_groupe}" required>
            </div>
            <div class="form-group">
                <label for="groupe-photo-${groupeId}">Photo du groupe :</label>
                <input type="file" name="photo_groupe" id="groupe-photo-${groupeId}" class="form-file" accept="image/*">
                <img src="${groupe.photo_groupe}" alt="${groupe.nom_groupe}" class="profile-img">
            </div>
            <div class="form-group">
                <label for="groupe-admin-${groupeId}">Administrateur :</label>
                <select name="admin_id" id="groupe-admin-${groupeId}" class="form-input" required>
                    <option value="${groupe.adminId}" selected>${groupe.admin}</option>
                    ${contactOptions}
                </select>
            </div>
            <div class="form-group">
                <label>Membres :</label>
                <ul class="contact-list" id="membres-list-${groupeId}">${membresList}</ul>
                <select name="new_member" class="form-input">
                    <option value="">Ajouter un membre</option>
                    ${contactOptions}
                </select>
            </div>
            <input type="hidden" name="groupe_id" value="${groupeId}">
            <input type="hidden" name="remove_member_id" id="remove-member-id-${groupeId}">
            <button type="submit" name="update_groupe" class="sidebar-button">Mettre à jour</button>
        </form>
    `;
}

function updateSettingsLink(user) {
    if (settingsLink) {
        settingsLink.href = `settings.php?user=${user}`;
        console.log('Lien Paramètres mis à jour:', settingsLink.href);
    } else {
        console.error('Lien Paramètres non trouvé');
    }
}

function switchConversation(e) {
    const selectedItem = e.currentTarget;
    console.log('Chat item cliqué:', selectedItem.dataset.user);
    chatItems.forEach(item => item.classList.remove("active"));
    selectedItem.classList.add("active");

    const user = selectedItem.dataset.user;
    const name = selectedItem.dataset.name;
    const avatar = selectedItem.dataset.avatar;

    // Update chat header
    chatHeader.innerHTML = `
        <img src="${avatar}" alt="Avatar">
        <div class="contact-info">
            <h3>${name}</h3>
            <p>En ligne</p>
        </div>
    `;

    // Update chat body
    const isGroup = user.startsWith("groupe-");
    const userMessages = window.messages[user] || [];
    chatBody.innerHTML = userMessages.map(msg => createMessageHTML(msg, isGroup)).join("");
    console.log('Messages affichés:', userMessages);

    // Update right sidebar
    if (!dynamicSettings) {
        console.error('Élément #dynamic-settings non trouvé');
        return;
    }
    if (user.startsWith("contact-")) {
        const contactId = user.replace("contact-", "");
        const contact = window.contacts[user];
        if (contact) {
            dynamicSettings.innerHTML = createContactSettings(contact, contactId);
        } else {
            console.error('Contact non trouvé:', user);
            dynamicSettings.innerHTML = '<h3>Erreur</h3><p>Contact non trouvé.</p>';
        }
    } else if (user.startsWith("groupe-")) {
        const groupeId = user.replace("groupe-", "");
        const groupe = window.groupes[user];
        if (groupe) {
            dynamicSettings.innerHTML = createGroupSettings(groupe, groupeId);
            // Add event listeners for remove member buttons
            const removeButtons = document.querySelectorAll(".remove-member");
            removeButtons.forEach(button => {
                button.addEventListener("click", () => {
                    const memberId = button.dataset.memberId;
                    const removeInput = document.querySelector(`#remove-member-id-${groupeId}`);
                    if (removeInput) {
                        removeInput.value = memberId;
                        button.closest("form").submit();
                    }
                });
            });
        } else {
            console.error('Groupe non trouvé:', user);
            dynamicSettings.innerHTML = '<h3>Erreur</h3><p>Groupe non trouvé.</p>';
        }
    } else {
        console.error('Type d’utilisateur non reconnu:', user);
        dynamicSettings.innerHTML = '<h3>Erreur</h3><p>Type d’utilisateur non reconnu.</p>';
    }

    // Update settings link
    updateSettingsLink(user);

    chatBody.scrollTop = chatBody.scrollHeight;
}

chatItems.forEach(item => {
    item.addEventListener("click", switchConversation);
});

if (chatItems.length > 0) {
    console.log('Sélection du premier chat item');
    chatItems[0].click();
} else {
    console.log('Aucun chat item trouvé');
}