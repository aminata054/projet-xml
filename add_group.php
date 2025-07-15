<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un groupe</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="enhanced_style.css">
    <style>
        /* Ajout de styles inline pour rendre le formulaire carré avec une taille augmentée */
        .chat-container {
            width: 700px; /* Augmentation de la largeur pour un carré plus grand */
            height: 700px; /* Hauteur ajustée pour égaler la largeur, créant un carré */
            overflow-y: auto; /* Permet de scroller si le contenu dépasse */
            padding: 20px; /* Conservé pour un espacement adéquat */
        }
        /* Ajustement du formulaire pour s'adapter au conteneur carré */
        .profile-form {
            max-height: 70%; /* Limite la hauteur du formulaire pour laisser de l'espace */
            overflow-y: auto; /* Permet de scroller si nécessaire */
        }
        /* Ajustement des sections pour éviter le débordement */
        .search-members, .recent-groups {
            max-height: 20%; /* Limite la hauteur des sections */
            overflow-y: auto; /* Permet de scroller si nécessaire */
        }
    </style>
</head>

<body>
    <div class="container chat-container">
        <div class="form-header">
            <h2>Créer un groupe</h2>
            <a href="index.php" class="back-button">← Retour</a>
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
        
        <!-- Groupes récents -->
        <div class="recent-groups">
            <h3>Groupes récemment créés</h3>
            <div id="recent-groups-list">
                <!-- Sera rempli par JavaScript -->
            </div>
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
        
        // Validation en temps réel
        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('change', validateField);
        });
        
        // Prévisualisation de la photo
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('La taille du fichier ne doit pas dépasser 2MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `
                        <img src="${e.target.result}" alt="Prévisualisation" class="preview-img">
                        <button type="button" onclick="clearPhotoPreview()" class="remove-preview">×</button>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Soumission du formulaire
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
            // Désélectionner
            selectedMembers.delete(contactId);
            element.classList.remove('selected');
            indicator.textContent = '+';
            removeMemberFromSelected(contactId);
        } else {
            // Sélectionner
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
        // Supprimer les anciens inputs cachés
        const oldInputs = document.querySelectorAll('input[name="membres[]"]');
        oldInputs.forEach(input => input.remove());
        
        // Ajouter les nouveaux inputs cachés
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
        
        // Valider les membres
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
        
        // Afficher le loading
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(form);
            const response = await fetch('add.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                showNotification('Groupe créé avec succès!', 'success');
                
                // Logger l'action
                logAction('create_group', `Nouveau groupe: ${formData.get('nom_groupe')} (${selectedMembers.size} membres)`);
                
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                throw new Error('Erreur lors de la création du groupe');
            }
        } catch (error) {
            showNotification('Erreur lors de la création du groupe', 'error');
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
            console.error('Erreur chargement historique:', error);
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
        }
    }
    </script>
</body>

</html>