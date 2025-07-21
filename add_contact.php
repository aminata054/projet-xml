<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un contact - WhatsApp Web</title>
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
        .formogyan
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
        .form-input.valid {
            border-color: #25d366;
        }
        .error-message {
            color: #e53935;
            font-size: 0.85em;
            min-height: 20px;
        }
        .file-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        .preview-img {
            max-width: 100px;
            border-radius: 50%;
        }
        .remove-preview {
            background: #e53935;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 0.9em;
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
        .search-container {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .search-results {
            margin-top: 10px;
        }
        .search-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .recent-contacts {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .recent-contact-item {
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
    <div class="container chat-container" role="main">
        <div class="form-header">
            <h2 aria-label="Ajouter un nouveau contact"><i class="fas fa-user-plus"></i> Ajouter un contact</h2>
            <a href="index.php" class="back-button sidebar-button" aria-label="Retour à la page principale"><i class="fas fa-arrow-left"></i></a>
        </div>
        
        <div class="search-container" role="search">
            <label for="contact-search" class="visually-hidden">Rechercher un contact existant</label>
            <input type="text" id="contact-search" placeholder="🔍 Rechercher un contact..." class="search-input form-input" aria-describedby="search-hint">
            <small id="search-hint" class="form-help">Recherchez pour éviter les doublons</small>
            <div id="contact-search-results" class="search-results hidden" role="list"></div>
        </div>
        
        <form method="post" action="add.php" enctype="multipart/form-data" class="profile-form" id="add-contact-form" aria-label="Formulaire d'ajout de contact">
            <div class="form-group">
                <label for="prenom">Prénom <span class="required" aria-hidden="true">*</span>:</label>
                <div class="input-wrapper">
                    <input type="text" name="prenom" id="prenom" class="form-input" required 
                           pattern="[A-Za-zÀ-ÿ\s]+" title="Seules les lettres sont autorisées" aria-describedby="prenom-error">
                    <span class="input-status" aria-hidden="true"></span>
                </div>
                <div class="error-message" id="prenom-error" role="alert"></div>
            </div>
            
            <div class="form-group">
                <label for="nom">Nom <span class="required" aria-hidden="true">*</span>:</label>
                <div class="input-wrapper">
                    <input type="text" name="nom" id="nom" class="form-input" required 
                           pattern="[A-Za-zÀ-ÿ\s]+" title="Seules les lettres sont autorisées" aria-describedby="nom-error">
                    <span class="input-status" aria-hidden="true"></span>
                </div>
                <div class="error-message" id="nom-error" role="alert"></div>
            </div>
            
            <div class="form-group">
                <label for="numero">Numéro de téléphone <span class="required" aria-hidden="true">*</span>:</label>
                <div class="input-wrapper">
                    <input type="tel" name="numero_telephone" id="numero" class="form-input" required 
                           pattern="[0-9+\-\s]+" title="Format: +221 77 123 45 67" aria-describedby="numero-error">
                    <span class="input-status" aria-hidden="true"></span>
                </div>
                <div class="error-message" id="numero-error" role="alert"></div>
            </div>
            
            <div class="form-group">
                <label for="photo">Photo de profil :</label>
                <input type="file" name="photo" id="photo" class="form-file" accept="image/*" aria-describedby="photo-help">
                <div class="file-preview" id="photo-preview"></div>
                <small id="photo-help" class="form-help">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
            </div>
            
            <div class="form-group">
                <label for="status">Statut :</label>
                <select name="status" id="status" class="form-input" aria-label="Sélectionner le statut du contact">
                    <option value="En ligne">En ligne</option>
                    <option value="Hors ligne" selected>Hors ligne</option>
                    <option value="Occupé">Occupé</option>
                    <option value="Absent">Absent</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="ajouter_contact" class="sidebar-button primary" id="submit-btn" aria-label="Ajouter le contact">
                    <span class="btn-text">Ajouter le contact</span>
                    <span class="btn-loading hidden loading">Ajout en cours...</span>
                </button>
                <button type="reset" class="sidebar-button secondary" aria-label="Effacer le formulaire">Effacer</button>
            </div>
        </form>
        
        <div class="recent-contacts" role="region" aria-label="Contacts récemment ajoutés">
            <h3>Contacts récemment ajoutés</h3>
            <div id="recent-contacts-list" role="list"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeContactForm();
        loadRecentContacts();
        initializeContactSearch();
    });

    function initializeContactForm() {
        const form = document.getElementById('add-contact-form');
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photo-preview');
        
        const inputs = form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('input', validateField);
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearError);
        });
        
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('La taille du fichier ne doit pas dépasser 2MB', 'error');
                    this.value = '';
                    photoPreview.innerHTML = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `
                        <img src="${e.target.result}" alt="Prévisualisation de la photo de profil" class="preview-img">
                        <button type="button" onclick="clearPhotoPreview()" class="remove-preview" aria-label="Supprimer la photo">×</button>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                photoPreview.innerHTML = '';
            }
        });
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (validateForm()) {
                await submitForm();
            }
        });

        form.querySelectorAll('input, select, button').forEach(element => {
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && this.tagName !== 'BUTTON') {
                    e.preventDefault();
                    const nextElement = this.closest('.form-group').nextElementSibling?.querySelector('input, select, button') || 
                                      form.querySelector('.form-actions button[type="submit"]');
                    if (nextElement) nextElement.focus();
                }
            });
        });
    }

    function validateField(e) {
        const field = e.target;
        const errorDiv = document.getElementById(field.id + '-error');
        const statusSpan = field.parentElement.querySelector('.input-status');
        let isValid = true;
        let errorMessage = '';
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        } else if (field.type === 'tel' && field.value) {
            const phoneRegex = /^[0-9+\-\s]+$/;
            if (!phoneRegex.test(field.value)) {
                isValid = false;
                errorMessage = 'Format de numéro invalide (ex: +221 77 123 45 67)';
            }
        } else if ((field.id === 'prenom' || field.id === 'nom') && field.value) {
            const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
            if (!nameRegex.test(field.value)) {
                isValid = false;
                errorMessage = 'Seules les lettres sont autorisées';
            }
        }
        
        if (isValid) {
            field.classList.remove('error');
            field.classList.add('valid');
            errorDiv.textContent = '';
            statusSpan.className = 'input-status valid';
            statusSpan.textContent = '✓';
        } else {
            field.classList.remove('valid');
            field.classList.add('error');
            errorDiv.textContent = errorMessage;
            statusSpan.className = 'input-status error';
            statusSpan.textContent = '✗';
        }
        
        return isValid;
    }

    function clearError(e) {
        const field = e.target;
        const errorDiv = document.getElementById(field.id + '-error');
        const statusSpan = field.parentElement.querySelector('.input-status');
        field.classList.remove('error', 'valid');
        errorDiv.textContent = '';
        statusSpan.className = 'input-status';
        statusSpan.textContent = '';
    }

    function validateForm() {
        const form = document.getElementById('add-contact-form');
        const inputs = form.querySelectorAll('input[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField({target: input})) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    async function submitForm() {
        const form = document.getElementById('add-contact-form');
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
                showNotification('Contact ajouté avec succès !', 'success');
                await logAction('add_contact', `Nouveau contact: ${formData.get('prenom')} ${formData.get('nom')}`);
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                showNotification(result.message || 'Erreur lors de l\'ajout du contact', 'error');
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

    function clearPhotoPreview() {
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photo-preview');
        photoInput.value = '';
        photoPreview.innerHTML = '';
    }

    function initializeContactSearch() {
        const searchInput = document.getElementById('contact-search');
        const searchResults = document.getElementById('contact-search-results');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchContacts(query);
            }, 300);
        });
    }

    async function searchContacts(query) {
        try {
            const response = await fetch(`search.php?action=search_contacts&q=${encodeURIComponent(query)}`);
            const contacts = await response.json();
            
            const searchResults = document.getElementById('contact-search-results');
            
            if (contacts.length > 0) {
                let html = '<div class="search-section" role="list"><h4>Contacts existants</h4>';
                contacts.forEach(contact => {
                    html += `
                        <div class="search-item existing-contact" role="listitem">
                            <img src="${contact.photo || 'images/default-avatar.png'}" alt="Photo de ${contact.prenom} ${contact.nom}" class="contact-avatar">
                            <div class="search-item-info">
                                <span class="contact-name">${contact.prenom} ${contact.nom}</span>
                                <small class="duplicate-warning">⚠️ Contact déjà existant</small>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                searchResults.innerHTML = html;
                searchResults.classList.remove('hidden');
            } else {
                searchResults.innerHTML = '<p class="no-results">Aucun contact trouvé</p>';
                searchResults.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erreur recherche:', error);
            showNotification('Erreur recherche contacts', 'error');
        }
    }

    async function loadRecentContacts() {
        try {
            const response = await fetch('history.php?action=get_history&limit=5');
            const history = await response.json();
            
            const recentList = document.getElementById('recent-contacts-list');
            const addContactEntries = history.filter(entry => entry.action === 'add_contact');
            
            if (addContactEntries.length > 0) {
                let html = '';
                addContactEntries.forEach(entry => {
                    html += `
                        <div class="recent-contact-item contact-item" role="listitem">
                            <span class="contact-name">${entry.details}</span>
                            <small class="contact-date">${new Date(entry.timestamp).toLocaleString('fr-FR')}</small>
                        </div>
                    `;
                });
                recentList.innerHTML = html;
            } else {
                recentList.innerHTML = '<p class="no-recent">Aucun contact récent</p>';
            }
        } catch (error) {
            console.error('Erreur historique:', error);
            showNotification('Erreur chargement contacts récents', 'error');
        }
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `toast-notification ${type}`;
        notification.innerHTML = `<div class="toast-body">${message}</div>`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        
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
