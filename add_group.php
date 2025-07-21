<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un groupe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .chat-container {
            width: 600px;
            height: 600px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            padding: 20px;
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #075e54;
            color: #fff;
            border-radius: 10px 10px 0 0;
        }
        .form-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        .back-button {
            color: #fff;
            text-decoration: none;
            font-size: 1.2em;
            transition: opacity 0.3s;
        }
        .back-button:hover {
            opacity: 0.8;
        }
        .profile-form {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        .form-input, .form-file {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        .form-input:focus, .form-file:focus {
            outline: none;
            border-color: #25d366;
        }
        .form-input.error {
            border-color: #e53935;
        }
        .members-selection {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .search-members {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .available-contacts, .selected-members {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 10px;
        }
        .contacts-list, .selected-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .contact-item, .selected-member {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .contact-item:hover, .selected-member:hover {
            background: #f0f2f5;
        }
        .contact-item.selected {
            background: #e0f7fa;
        }
        .contact-avatar, .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .selection-indicator {
            margin-left: auto;
            color: #25d366;
            font-weight: bold;
        }
        .remove-member {
            background: #e53935;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .sidebar-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .sidebar-button.primary {
            background: #25d366;
            color: #fff;
        }
        .sidebar-button.primary:hover {
            background: #20b058;
        }
        .sidebar-button.secondary {
            background: #eceff1;
            color: #333;
        }
        .sidebar-button.secondary:hover {
            background: #dfe3e6;
        }
        .btn-loading {
            display: none;
        }
        .btn-loading.loading {
            display: inline;
        }
        .recent-groups {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .recent-group-item {
            padding: 10px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: #fff;
            transition: opacity 0.3s;
        }
        .toast-notification.success {
            background: #25d366;
        }
        .toast-notification.error {
            background: #e53935;
        }
        .toast-notification.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container chat-container">
        <div class="form-header">
            <h2><i class="fas fa-users"></i> Créer un groupe</h2>
            <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
        </div>
        
        <form method="post" action="add.php" enctype="multipart/form-data" class="profile-form" id="create-group-form">
            <div class="form-group">
                <label for="nom_groupe">Nom du groupe <span class="required">*</span>:</label>
                <input type="text" name="nom_groupe" id="nom_groupe" class="form-input" required 
                       maxlength="50" placeholder="Ex: Projet XML">
                <div class="error-message" id="nom_groupe-error"></div>
                <small class="form-help">Maximum 50 caractères</small>
            </div>
            
            <div class="form-group">
                <label for="admin_id">Administrateur <span class="required">*</span>:</label>
                <select name="admin_id" id="admin_id" class="form-input" required>
                    <option value="">Sélectionner un administrateur</option>
                    <?php
                    $xml = simplexml_load_file('whatsapp.xml');
                    foreach ($xml->discussions->contacts->contact as $contact) {
                        $contactId = (string)$contact['id'];
                        $fullName = htmlspecialchars((string)$contact->prenom . ' ' . (string)$contact->nom);
                        echo "<option value='$contactId'>$fullName</option>";
                    }
                    ?>
                </select>
                <div class="error-message" id="admin_id-error"></div>
            </div>
            
            <div class="form-group">
                <label>Membres du groupe <span class="required">*</span>:</label>
                <div class="members-selection">
                    <div class="search-members">
                        <input type="text" id="member-search" placeholder="Rechercher des contacts..." class="search-input">
                    </div>
                    <div class="available-contacts">
                        <h4>Contacts disponibles</h4>
                        <div class="contacts-list" id="available-contacts-list">
                            <?php
                            foreach ($xml->discussions->contacts->contact as $contact) {
                                $contactId = (string)$contact['id'];
                                $fullName = htmlspecialchars((string)$contact->prenom . ' ' . (string)$contact->nom);
                                $photo = htmlspecialchars((string)$contact->photo_profile);
                                echo "
                                <div class='contact-item selectable' data-contact-id='$contactId' onclick='toggleMember(this)'>
                                    <img src='$photo' alt='$fullName' class='contact-avatar'>
                                    <span class='contact-name'>$fullName</span>
                                    <span class='selection-indicator'>+</span>
                                </div>";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="selected-members">
                        <h4>Membres sélectionnés (<span id="member-count">0</span>)</h4>
                        <div class="selected-list" id="selected-members-list">
                            <p class="no-members">Aucun membre sélectionné</p>
                        </div>
                    </div>
                </div>
                <div class="error-message" id="membres-error"></div>
                <small class="form-help">Sélectionnez au moins 2 membres pour créer un groupe</small>
            </div>
            
            <div class="form-group">
                <label for="photo">Photo du groupe :</label>
                <input type="file" name="photo_groupe" id="photo" class="form-file" accept="image/*">
                <div class="file-preview" id="photo-preview"></div>
                <small class="form-help">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="creer_groupe" class="sidebar-button primary" id="submit-btn">
                    <span class="btn-text">Créer le groupe</span>
                    <span class="btn-loading hidden">Création en cours...</span>
                </button>
                <button type="reset" class="sidebar-button secondary" onclick="resetForm()">Effacer</button>
            </div>
        </form>
        
        <div class="recent-groups">
            <h3>Groupes récemment créés</h3>
            <div id="recent-groups-list"></div>
        </div>
    </div>

    <script>
    let selectedMembers = new Set();

    document.addEventListener('DOMContentLoaded', function() {
        initializeGroupForm();
        initializeMemberSearch();
        loadRecentGroups();
    });

    function initializeGroupForm() {
        const form = document.getElementById('create-group-form');
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photo-preview');
        
        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('change', validateField);
        });
        
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('La taille du fichier ne doit pas dépasser 2MB', 'error');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `
                        <img src="${e.target.result}" alt="Prévisualisation" class="preview-img" style="max-width: 100px; border-radius: 50%;">
                        <button type="button" onclick="clearPhotoPreview()" class="remove-preview">×</button>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm()) {
                submitForm();
            }
        });
    }

    function initializeMemberSearch() {
        const searchInput = document.getElementById('member-search');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const contactItems = document.querySelectorAll('#available-contacts-list .contact-item');
            
            contactItems.forEach(item => {
                const name = item.querySelector('.contact-name').textContent.toLowerCase();
                if (name.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    function toggleMember(element) {
        const contactId = element.dataset.contactId;
        const contactName = element.querySelector('.contact-name').textContent;
        const contactImg = element.querySelector('.contact-avatar').src;
        const indicator = element.querySelector('.selection-indicator');
        
        if (selectedMembers.has(contactId)) {
            selectedMembers.delete(contactId);
            element.classList.remove('selected');
            indicator.textContent = '+';
            removeMemberFromSelected(contactId);
        } else {
            selectedMembers.add(contactId);
            element.classList.add('selected');
            indicator.textContent = '−';
            addMemberToSelected(contactId, contactName, contactImg);
        }
        
        updateMemberCount();
        updateMembersInput();
    }

    function addMemberToSelected(contactId, contactName, contactImg) {
        const selectedList = document.getElementById('selected-members-list');
        const noMembers = selectedList.querySelector('.no-members');
        
        if (noMembers) {
            noMembers.remove();
        }
        
        const memberDiv = document.createElement('div');
        memberDiv.className = 'selected-member';
        memberDiv.dataset.contactId = contactId;
        memberDiv.innerHTML = `
            <img src="${contactImg}" alt="${contactName}" class="member-avatar">
            <span class="member-name">${contactName}</span>
            <button type="button" class="remove-member" onclick="removeMember('${contactId}')">×</button>
        `;
        
        selectedList.appendChild(memberDiv);
    }

    function removeMemberFromSelected(contactId) {
        const memberDiv = document.querySelector(`#selected-members-list .selected-member[data-contact-id="${contactId}"]`);
        if (memberDiv) {
            memberDiv.remove();
        }
        
        const selectedList = document.getElementById('selected-members-list');
        if (selectedList.children.length === 0) {
            selectedList.innerHTML = '<p class="no-members">Aucun membre sélectionné</p>';
        }
    }

    function removeMember(contactId) {
        const contactItem = document.querySelector(`#available-contacts-list .contact-item[data-contact-id="${contactId}"]`);
        if (contactItem) {
            toggleMember(contactItem);
        }
    }

    function updateMemberCount() {
        document.getElementById('member-count').textContent = selectedMembers.size;
    }

    function updateMembersInput() {
        const oldInputs = document.querySelectorAll('input[name="membres[]"]');
        oldInputs.forEach(input => input.remove());
        
        const form = document.getElementById('create-group-form');
        selectedMembers.forEach(memberId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'membres[]';
            input.value = memberId;
            form.appendChild(input);
        });
    }

    function validateField(e) {
        const field = e.target;
        const errorDiv = document.getElementById(field.id + '-error');
        let isValid = true;
        let errorMessage = '';
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        }
        
        if (isValid) {
            field.classList.remove('error');
            if (errorDiv) errorDiv.textContent = '';
        } else {
            field.classList.add('error');
            if (errorDiv) errorDiv.textContent = errorMessage;
        }
        
        return isValid;
    }

    function validateForm() {
        const form = document.getElementById('create-group-form');
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField({target: input})) {
                isValid = false;
            }
        });
        
        const membersError = document.getElementById('membres-error');
        if (selectedMembers.size < 2) {
            isValid = false;
            membersError.textContent = 'Sélectionnez au moins 2 membres';
        } else {
            membersError.textContent = '';
        }
        
        return isValid;
    }

    async function submitForm() {
        const form = document.getElementById('create-group-form');
        const submitBtn = document.getElementById('submit-btn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(form);
            const response = await fetch('add.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Groupe créé avec succès !', 'success');
                logAction('create_group', `Nouveau groupe: ${formData.get('nom_groupe')} (${selectedMembers.size} membres)`);
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                showNotification(result.message || 'Erreur création groupe', 'error');
            }
        } catch (error) {
            showNotification('Erreur: ' + error.message, 'error');
            console.error('Erreur:', error);
        } finally {
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    function resetForm() {
        selectedMembers.clear();
        document.querySelectorAll('.contact-item.selected').forEach(item => {
            item.classList.remove('selected');
            item.querySelector('.selection-indicator').textContent = '+';
        });
        document.getElementById('selected-members-list').innerHTML = '<p class="no-members">Aucun membre sélectionné</p>';
        updateMemberCount();
        clearPhotoPreview();
    }

    function clearPhotoPreview() {
        document.getElementById('photo-preview').innerHTML = '';
        document.getElementById('photo').value = '';
    }

    async function loadRecentGroups() {
        try {
            const response = await fetch('history.php?action=get_history&limit=5');
            const history = await response.json();
            
            const recentList = document.getElementById('recent-groups-list');
            const createGroupEntries = history.filter(entry => entry.action === 'create_group');
            
            if (createGroupEntries.length > 0) {
                let html = '';
                createGroupEntries.forEach(entry => {
                    html += `
                        <div class="recent-group-item">
                            <span class="group-name">${entry.details}</span>
                            <small class="group-date">${new Date(entry.timestamp).toLocaleString()}</small>
                        </div>
                    `;
                });
                recentList.innerHTML = html;
            } else {
                recentList.innerHTML = '<p class="no-recent">Aucun groupe récent</p>';
            }
        } catch (error) {
            console.error('Erreur historique:', error);
            showNotification('Erreur chargement groupes récents', 'error');
        }
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `toast-notification ${type}`;
        notification.innerHTML = `<div class="toast-body">${message}</div>`;
        
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    async function logAction(action, details) {
        try {
            await fetch('history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${encodeURIComponent(action)}&details=${encodeURIComponent(details)}`
            });
        } catch (error) {
            console.error('Erreur log:', error);
            showNotification('Erreur enregistrement action', 'error');
        }
    }
    </script>
</body>
</html>
