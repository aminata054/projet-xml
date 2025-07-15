<?php
// Enable libxml error handling
libxml_use_internal_errors(true);

// Inclure les nouvelles fonctionnalités
require_once 'history.php';
require_once 'auto_save.php';

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    echo "Erreur lors du chargement du fichier XML:<br>";
    foreach (libxml_get_errors() as $error) {
        echo $error->message . " at line " . $error->line . "<br>";
    }
    exit;
}

$history = new HistoryManager();
$autoSave = new AutoSaveManager('whatsapp.xml');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - WhatsApp Web</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="enhanced_style.css" />
    <style>
        /* Styles spécifiques pour les paramètres avec couleurs WhatsApp */
        .settings-container {
            background: #075e54; /* Vert foncé WhatsApp */
            min-height: 100vh;
            padding: 20px;
            color: #ffffff; /* Texte blanc pour contraste */
        }
        
        .settings-wrapper {
            background: #ffffff; /* Fond blanc pour le contenu */
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 800px; /* Ajustement pour un carré */
            height: 800px; /* Ajustement pour un carré */
            margin: 0 auto;
            overflow-y: auto; /* Permet de scroller si le contenu dépasse */
        }
        
        .settings-header {
            background: #25D366; /* Vert WhatsApp pour l'en-tête */
            color: #ffffff;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .settings-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        
        .back-button {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .settings-navigation {
            background: #128C7E; /* Vert moyen WhatsApp */
            max-height: 15%; /* Limite la hauteur de la navigation */
            overflow-y: auto; /* Permet de scroller si nécessaire */
            padding: 10px 0;
        }
        
        .nav-tab {
            background: none;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            text-align: left;
        }
        
        .nav-tab.active {
            background: #25D366; /* Vert clair WhatsApp pour l'onglet actif */
        }
        
        .nav-tab:hover {
            background: #075e54; /* Vert foncé au survol */
        }
        
        .settings-content {
            max-height: 85%; /* Limite la hauteur pour laisser de l'espace à la navigation */
            overflow-y: auto; /* Permet de scroller si nécessaire */
            padding: 20px;
            background: #ffffff; /* Fond blanc pour le contenu */
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modern-btn.primary {
            background: #25D366; /* Vert WhatsApp pour les boutons primaires */
            color: #ffffff;
        }
        
        .modern-btn.primary:hover {
            background: #128C7E; /* Vert moyen au survol */
        }
        
        /* Ajustement pour la liste des contacts */
        .contacts-grid {
            display: block; /* Changement de grille à liste */
        }
        
        .contact-card {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f7f7f7;
            border-radius: 10px;
            transition: background 0.3s ease;
        }
        
        .contact-card:hover {
            background: #e0e0e0;
        }
        
        .avatar-container {
            margin-right: 15px;
        }
        
        .contact-info {
            flex-grow: 1;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Ajustement pour les groupes */
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .group-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 150px; /* Hauteur fixe pour éviter le débordement */
            padding: 15px;
            background: #f7f7f7;
            border-radius: 10px;
            transition: background 0.3s ease;
            overflow: hidden; /* Empêche le débordement */
        }
        
        .group-card:hover {
            background: #e0e0e0;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .group-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .group-name {
            margin-top: auto; /* Déplace le nom en bas */
            font-weight: bold;
            word-wrap: break-word; /* Permet le retour à la ligne */
            max-width: 100%;
        }
    </style>
</head>

<body>
    <div class="settings-container" role="main">
        <div class="settings-wrapper">
            <div class="settings-header">
                <h2 aria-label="Paramètres">⚙️ Paramètres</h2>
                <a href="index.php" class="back-button" aria-label="Retour à la page de chat">← Retour au chat</a>
            </div>

            <!-- Navigation par onglets améliorée -->
            <nav class="settings-navigation" role="tablist">
                <button class="nav-tab active" onclick="showTab('contacts')" data-icon="contacts" role="tab" aria-selected="true" aria-controls="contacts-tab">
                    <span class="tab-icon">👥</span>
                    <span class="tab-text">Contacts</span>
                    <span class="tab-count"><?php echo count($xml->discussions->contacts->contact); ?></span>
                </button>
                <button class="nav-tab" onclick="showTab('groups')" data-icon="groups" role="tab" aria-selected="false" aria-controls="groups-tab">
                    <span class="tab-icon">👨‍👩‍👧‍👦</span>
                    <span class="tab-text">Groupes</span>
                    <span class="tab-count"><?php echo count($xml->discussions->groupes->groupe); ?></span>
                </button>
                <button class="nav-tab" onclick="showTab('parameter')" data-icon="advanced" role="tab" aria-selected="false" aria-controls="parameter-tab">
                    <span class="tab-icon">🔧</span>
                    <span class="tab-text">Paramètre</span>
                </button>
            </nav>

            <div class="settings-content">
                <!-- Onglet Contacts amélioré -->
                <section id="contacts-tab" class="tab-panel active" role="tabpanel" aria-labelledby="contacts">
                    <header class="panel-header">
                        <h3>👥 Gestion des contacts</h3>
                        <div class="panel-actions">
                            <div class="search-box">
                                <input type="text" id="contact-search" placeholder="🔍 Rechercher un contact..." class="modern-search" aria-label="Rechercher un contact">
                            </div>
                            <a href="add_contact.php" class="modern-btn primary" aria-label="Ajouter un nouveau contact">
                                <span>➕</span> Nouveau contact
                            </a>
                        </div>
                    </header>
                    
                    <div class="stats-grid" role="region" aria-label="Statistiques des contacts">
                        <div class="stat-card primary">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count($xml->discussions->contacts->contact); ?></span>
                                <span class="stat-label">Contacts total</span>
                            </div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon">🟢</div>
                            <div class="stat-info">
                                <span class="stat-number"><?php 
                                    $onlineCount = 0;
                                    foreach ($xml->discussions->contacts->contact as $contact) {
                                        if ((string)$contact->status === 'En ligne') $onlineCount++;
                                    }
                                    echo $onlineCount;
                                ?></span>
                                <span class="stat-label">En ligne</span>
                            </div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon">🔴</div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count($xml->discussions->contacts->contact) - $onlineCount; ?></span>
                                <span class="stat-label">Hors ligne</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contacts-grid" id="contacts-list" role="list">
                        <?php
                        foreach ($xml->discussions->contacts->contact as $contact) {
                            $contactId = (string)$contact['id'];
                            $fullName = htmlspecialchars((string)$contact->prenom . ' ' . (string)$contact->nom);
                            $photo = htmlspecialchars((string)$contact->photo_profile);
                            $status = htmlspecialchars((string)$contact->status);
                            $numero = htmlspecialchars((string)$contact->numero_telephone);
                            $statusClass = $status === 'En ligne' ? 'online' : 'offline';
                            
                            // Vérifier si l'image existe, sinon utiliser une image par défaut
                            if (!file_exists($photo) || empty($photo)) {
                                $photo = 'images/default-avatar.png';
                            }
                            
                            echo "
                            <div class='contact-card modern' data-contact-name='$fullName' role='listitem'>
                                <div class='card-header'>
                                    <div class='avatar-container'>
                                        <img src='$photo' alt='Photo de profil de $fullName' class='contact-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFNUU3RUIiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSIjOUM5Qzk5Ii8+CjxwYXRoIGQ9Ik0xMiAxNEM5LjMzIDEzLjk5IDcuMDEgMTUuNjIgNi4yMiAxOC4wNEM2LjA5IDE4LjQ2IDYuNDEgMTguODggNi44NiAxOC84OEgxNy4xNEMxNy41OSAxOC44OCAxNy45MSAxOC40NiAxNy43OCAxOC4wNEMxNi45OSAxNS42MiAxNC42NyAxMy45OSAxMiAxNFoiIGZpbGw9IiM5QzlDOTkiLz4KPC9zdmc+Cjwvc3ZnPgo='\">
                                        <div class='status-dot $statusClass' aria-label='Statut $status'></div>
                                    </div>
                                    <div class='contact-info'>
                                        <h4 class='contact-name'>$fullName</h4>
                                        <p class='contact-phone'>📞 $numero</p>
                                        <span class='contact-status $statusClass'>$status</span>
                                    </div>
                                </div>
                                <div class='card-actions'>
                                    <button onclick='editContact($contactId)' class='action-btn edit' title='Modifier le contact' aria-label='Modifier $fullName'>
                                        <span>✏️</span>
                                    </button>
                                    <button onclick='viewMessages($contactId)' class='action-btn view' title='Voir les messages' aria-label='Voir les messages de $fullName'>
                                        <span>💬</span>
                                    </button>
                                    <button onclick='deleteContact($contactId)' class='action-btn delete' title='Supprimer le contact' aria-label='Supprimer $fullName'>
                                        <span>🗑️</span>
                                    </button>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </section>

                <!-- Onglet Groupes amélioré -->
                <section id="groups-tab" class="tab-panel" role="tabpanel" aria-labelledby="groups">
                    <header class="panel-header">
                        <h3>👨‍👩‍👧‍👦 Gestion des groupes</h3>
                        <div class="panel-actions">
                            <div class="search-box">
                                <input type="text" id="group-search" placeholder="🔍 Rechercher un groupe..." class="modern-search" aria-label="Rechercher un groupe">
                            </div>
                            <a href="add_group.php" class="modern-btn primary" aria-label="Créer un nouveau groupe">
                                <span>➕</span> Nouveau groupe
                            </a>
                        </div>
                    </header>
                    
                    <div class="stats-grid" role="region" aria-label="Statistiques des groupes">
                        <div class="stat-card info">
                            <div class="stat-icon">👨‍👩‍👧‍👦</div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count($xml->discussions->groupes->groupe); ?></span>
                                <span class="stat-label">Groupes total</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="groups-grid" id="groups-list" role="list">
                        <?php
                        foreach ($xml->discussions->groupes->groupe as $groupe) {
                            $groupeId = (string)$groupe['id'];
                            $nomGroupe = htmlspecialchars((string)$groupe->nom_groupe);
                            $photo = htmlspecialchars((string)$groupe->photo_groupe);
                            $adminId = (string)$groupe->admin['ref'];
                            $membersCount = count($groupe->membres->contact);
                            
                            // Trouver le nom de l'admin
                            $adminContact = $xml->xpath("//contact[@id='$adminId']")[0];
                            $adminName = $adminContact ? (string)$adminContact->prenom . ' ' . (string)$adminContact->nom : 'Inconnu';
                            
                            if (!file_exists($photo) || empty($photo)) {
                                $photo = 'images/default-group.png';
                            }
                            
                            echo "
                            <div class='group-card modern' data-group-name='$nomGroupe' role='listitem'>
                                <div class='card-header'>
                                    <div class='avatar-container'>
                                        <img src='$photo' alt='Photo du groupe $nomGroupe' class='group-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFNUU3RUIiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1lbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2IDRDMTcuMSA0IDE4IDQuOSAxOCA2VjE4QzE4IDE5LjEgMTcuMSAyMCAxNiAyMEg0QzIuOSAyMCAyIDE5LjEgMiAxOFY2QzIgNC45IDIuOSA0IDQgNEgxNlpNMTYgNkg0VjE4SDE2VjZaTTggOEgxNFYxMEg8VjhaTTggMTJIMTRWMTRIOFYxMloiIGZpbGw9IiM9QzlDOTkiLz4KPC9zdmc+Cjwvc3ZnPgo='\">
                                        <div class='group-badge'>$membersCount</div>
                                    </div>
                                    <div class='group-info'>
                                        <p class='group-members'>👥 $membersCount membres</p>
                                        <span class='group-admin'>👑 Admin: $adminName</span>
                                    </div>
                                </div>
                                <div class='group-name-container'>
                                    <h4 class='group-name'>$nomGroupe</h4>
                                </div>
                                <div class='card-actions'>
                                    <button onclick='editGroup($groupeId)' class='action-btn edit' title='Modifier le groupe' aria-label='Modifier $nomGroupe'>
                                        <span>✏️</span>
                                    </button>
                                    <button onclick='viewGroupMessages($groupeId)' class='action-btn view' title='Voir les messages' aria-label='Voir les messages du groupe $nomGroupe'>
                                        <span>💬</span>
                                    </button>
                                    <button onclick='deleteGroup($groupeId)' class='action-btn delete' title='Supprimer le groupe' aria-label='Supprimer $nomGroupe'>
                                        <span>🗑️</span>
                                    </button>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </section>

                <!-- Onglet Paramètre -->
                <section id="parameter-tab" class="tab-panel" role="tabpanel" aria-labelledby="parameter">
                    <header class="panel-header">
                        <h3>🔧 Paramètres avancés</h3>
                    </header>
                    
                    <div class="advanced-grid" role="region" aria-label="Paramètres avancés">
                        <div class="setting-card">
                            <div class="setting-header">
                                <h4>🔔 Notifications</h4>
                            </div>
                            <div class="setting-content">
                                <label class="modern-switch">
                                    <input type="checkbox" id="notifications-enabled" checked aria-label="Activer ou désactiver les notifications en temps réel">
                                    <span class="switch-slider"></span>
                                    <span class="switch-label">Activer les notifications en temps réel</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeSettings();
        initializeSearch();
        initializeTabNavigation();
    });

    function initializeSettings() {
        // Charger les préférences sauvegardées
        const notificationsEnabled = localStorage.getItem('notifications-enabled') !== 'false';
        
        const notificationsCheckbox = document.getElementById('notifications-enabled');
        
        notificationsCheckbox.checked = notificationsEnabled;
        
        // Écouter les changements
        notificationsCheckbox.addEventListener('change', function() {
            localStorage.setItem('notifications-enabled', this.checked);
            showNotification(this.checked ? 'Notifications activées' : 'Notifications désactivées', this.checked ? 'success' : 'info');
        });
    }

    function initializeTabNavigation() {
        const tabs = document.querySelectorAll('.nav-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                showTab(this.getAttribute('aria-controls').replace('-tab', ''));
            });
            tab.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    showTab(this.getAttribute('aria-controls').replace('-tab', ''));
                }
            });
        });
    }

    function showTab(tabName) {
        // Masquer tous les onglets
        document.querySelectorAll('.tab-panel').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-hidden', 'true');
        });
        
        // Désactiver tous les boutons
        document.querySelectorAll('.nav-tab').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        
        // Afficher l'onglet sélectionné
        const selectedTab = document.getElementById(tabName + '-tab');
        selectedTab.classList.add('active');
        selectedTab.setAttribute('aria-hidden', 'false');
        
        const selectedButton = document.querySelector(`.nav-tab[aria-controls="${tabName}-tab"]`);
        selectedButton.classList.add('active');
        selectedButton.setAttribute('aria-selected', 'true');
        selectedButton.focus();

        // Ajouter une transition fluide
        selectedTab.style.opacity = '0';
        selectedTab.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            selectedTab.style.opacity = '1';
        }, 50);
    }

    function initializeSearch() {
        const contactSearch = document.getElementById('contact-search');
        const groupSearch = document.getElementById('group-search');

        contactSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const contacts = document.querySelectorAll('#contacts-list .contact-card');
            
            contacts.forEach(contact => {
                const name = contact.dataset.contactName.toLowerCase();
                contact.style.display = name.includes(query) ? 'flex' : 'none';
            });
        });
        
        groupSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const groups = document.querySelectorAll('#groups-list .group-card');
            
            groups.forEach(group => {
                const name = group.dataset.groupName.toLowerCase();
                group.style.display = name.includes(query) ? 'flex' : 'none';
            });
        });
    }

    // Fonctions pour les contacts
    function editContact(contactId) {
        window.location.href = `index.php?edit_contact=${contactId}`;
    }

    function deleteContact(contactId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
            fetch('add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `delete_contact=1&contact_id=${contactId}`
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      showNotification('Contact supprimé', 'success');
                      location.reload();
                  } else {
                      showNotification('Erreur lors de la suppression', 'error');
                  }
              }).catch(error => {
                  showNotification('Erreur de connexion', 'error');
              });
        }
    }

    function viewMessages(contactId) {
        window.location.href = `index.php?contact=${contactId}`;
    }

    // Fonctions pour les groupes
    function editGroup(groupId) {
        window.location.href = `index.php?edit_group=${groupId}`;
    }

    function deleteGroup(groupId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?')) {
            fetch('add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `delete_group=1&group_id=${groupId}`
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      showNotification('Groupe supprimé', 'success');
                      location.reload();
                  } else {
                      showNotification('Erreur lors de la suppression', 'error');
                  }
              }).catch(error => {
                  showNotification('Erreur de connexion', 'error');
              });
        }
    }

    function viewGroupMessages(groupId) {
        window.location.href = `index.php?group=${groupId}`;
    }

    // Fonctions utilitaires
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
    </script>
</body>
</html>