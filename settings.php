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
        /* Styles spécifiques pour les paramètres */
        .settings-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .settings-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .settings-header {
            background: linear-gradient(45deg, #2d2341, #3e2f5b);
            color: white;
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
            color: white;
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
                <button class="nav-tab" onclick="showTab('backup')" data-icon="backup" role="tab" aria-selected="false" aria-controls="backup-tab">
                    <span class="tab-icon">💾</span>
                    <span class="tab-text">Sauvegarde</span>
                </button>
                <button class="nav-tab" onclick="showTab('history')" data-icon="history" role="tab" aria-selected="false" aria-controls="history-tab">
                    <span class="tab-icon">📋</span>
                    <span class="tab-text">Historique</span>
                </button>
                <button class="nav-tab" onclick="showTab('advanced')" data-icon="advanced" role="tab" aria-selected="false" aria-controls="advanced-tab">
                    <span class="tab-icon">🔧</span>
                    <span class="tab-text">Avancé</span>
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
                                        <img src='$photo' alt='Photo de profil de $fullName' class='contact-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFNUU3RUIiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSIjOUM5Qzk5Ii8+CjxwYXRoIGQ9Ik0xMiAxNEM5LjMzIDEzLjk5IDcuMDEgMTUuNjIgNi4yMiAxOC4wNEM2LjA5IDE4LjQ2IDYuNDEgMTguODggNi44NiAxOC44OEgxNy4xNEMxNy41OSAxOC44OCAxNy45MSAxOC40NiAxNy43OCAxOC4wNEMxNi45OSAxNS42MiAxNC42NyAxMy45OSAxMiAxNFoiIGZpbGw9IiM5QzlDOTkiLz4KPC9zdmc+Cjwvc3ZnPgo='\">
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
                                        <img src='$photo' alt='Photo du groupe $nomGroupe' class='group-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFNUU3RUIiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2IDRDMTcuMSA0IDE4IDQuOSAxOCA2VjE4QzE4IDE5LjEgMTcuMSAyMCAxNiAyMEg0QzIuOSAyMCAyIDE5LjEgMiAxOFY2QzIgNC45IDIuOSA0IDQgNEgxNlpNMTYgNkg0VjE4SDE2VjZaTTggOEgxNFYxMEg4VjhaTTggMTJIMTRWMTRIOFYxMloiIGZpbGw9IiM5QzlDOTkiLz4KPC9zdmc+Cjwvc3ZnPgo='\">
                                        <div class='group-badge'>$membersCount</div>
                                    </div>
                                    <div class='group-info'>
                                        <h4 class='group-name'>$nomGroupe</h4>
                                        <p class='group-members'>👥 $membersCount membres</p>
                                        <span class='group-admin'>👑 Admin: $adminName</span>
                                    </div>
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

                <!-- Onglet Sauvegarde -->
                <section id="backup-tab" class="tab-panel" role="tabpanel" aria-labelledby="backup">
                    <header class="panel-header">
                        <h3>💾 Gestion des sauvegardes</h3>
                    </header>
                    
                    <div class="backup-section">
                        <div class="backup-controls">
                            <button onclick="createBackup()" class="modern-btn success" aria-label="Créer une nouvelle sauvegarde">
                                <span>💾</span> Créer une sauvegarde
                            </button>
                            <button onclick="loadBackupList()" class="modern-btn secondary" aria-label="Actualiser la liste des sauvegardes">
                                <span>🔄</span> Actualiser
                            </button>
                        </div>
                        
                        <div class="backup-status modern" id="backup-status" role="status">
                            <div class="status-icon">⏰</div>
                            <div class="status-info">
                                <p><strong>Dernière sauvegarde automatique:</strong></p>
                                <span id="last-backup">Chargement...</span>
                            </div>
                        </div>
                        
                        <div class="backup-list modern" id="backup-list" role="list">
                            <!-- Sera rempli par JavaScript -->
                        </div>
                    </div>
                </section>

                <!-- Onglet Historique -->
                <section id="history-tab" class="tab-panel" role="tabpanel" aria-labelledby="history">
                    <header class="panel-header">
                        <h3>📋 Historique des actions</h3>
                        <div class="panel-actions">
                            <select id="history-filter" onchange="filterHistory()" class="modern-select" aria-label="Filtrer l'historique des actions">
                                <option value="">Toutes les actions</option>
                                <option value="send_message">Messages envoyés</option>
                                <option value="add_contact">Contacts ajoutés</option>
                                <option value="create_group">Groupes créés</option>
                                <option value="update_contact">Contacts modifiés</option>
                            </select>
                            <button onclick="clearHistory()" class="modern-btn danger" aria-label="Vider l'historique">
                                <span>🗑️</span> Vider l'historique
                            </button>
                        </div>
                    </header>
                    
                    <div class="history-container" id="history-list" role="list">
                        <!-- Sera rempli par JavaScript -->
                    </div>
                </section>

                <!-- Onglet Avancé -->
                <section id="advanced-tab" class="tab-panel" role="tabpanel" aria-labelledby="advanced">
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
                        
                        <div class="setting-card">
                            <div class="setting-header">
                                <h4>💾 Sauvegarde automatique</h4>
                            </div>
                            <div class="setting-content">
                                <label class="modern-switch">
                                    <input type="checkbox" id="auto-save-enabled" checked aria-label="Activer ou désactiver la sauvegarde automatique">
                                    <span class="switch-slider"></span>
                                    <span class="switch-label">Sauvegarde automatique (toutes les 5 minutes)</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-card">
                            <div class="setting-header">
                                <h4>✅ Validation XML</h4>
                            </div>
                            <div class="setting-content">
                                <button onclick="validateXML()" class="modern-btn info" aria-label="Valider le fichier XML">
                                    <span>🔍</span> Valider le fichier XML
                                </button>
                                <div id="validation-result" class="validation-result" role="alert"></div>
                            </div>
                        </div>
                        
                        <div class="setting-card">
                            <div class="setting-header">
                                <h4>🛠️ Maintenance</h4>
                            </div>
                            <div class="setting-content">
                                <div class="maintenance-actions">
                                    <button onclick="optimizeXML()" class="modern-btn warning" aria-label="Optimiser le fichier XML">
                                        <span>⚡</span> Optimiser XML
                                    </button>
                                    <button onclick="exportData()" class="modern-btn info" aria-label="Exporter les données">
                                        <span>📤</span> Exporter données
                                    </button>
                                </div>
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
        loadBackupList();
        loadHistory();
        initializeSearch();
        initializeTabNavigation();
    });

    function initializeSettings() {
        // Charger les préférences sauvegardées
        const notificationsEnabled = localStorage.getItem('notifications-enabled') !== 'false';
        const autoSaveEnabled = localStorage.getItem('auto-save-enabled') !== 'false';
        
        const notificationsCheckbox = document.getElementById('notifications-enabled');
        const autoSaveCheckbox = document.getElementById('auto-save-enabled');
        
        notificationsCheckbox.checked = notificationsEnabled;
        autoSaveCheckbox.checked = autoSaveEnabled;
        
        // Écouter les changements
        notificationsCheckbox.addEventListener('change', function() {
            localStorage.setItem('notifications-enabled', this.checked);
            showNotification(this.checked ? 'Notifications activées' : 'Notifications désactivées', this.checked ? 'success' : 'info');
        });
        
        autoSaveCheckbox.addEventListener('change', function() {
            localStorage.setItem('auto-save-enabled', this.checked);
            showNotification(this.checked ? 'Sauvegarde automatique activée' : 'Sauvegarde automatique désactivée', this.checked ? 'success' : 'info');
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
                contact.style.display = name.includes(query) ? 'grid' : 'none';
            });
        });
        
        groupSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const groups = document.querySelectorAll('#groups-list .group-card');
            
            groups.forEach(group => {
                const name = group.dataset.groupName.toLowerCase();
                group.style.display = name.includes(query) ? 'grid' : 'none';
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

    // Fonctions de sauvegarde
    async function createBackup() {
        try {
            const response = await fetch('auto_save.php?action=create_backup');
            const result = await response.json();
            
            if (result.success) {
                showNotification('Sauvegarde créée avec succès', 'success');
                loadBackupList();
            } else {
                showNotification('Erreur lors de la sauvegarde', 'error');
            }
        } catch (error) {
            showNotification('Erreur de connexion', 'error');
        }
    }

    async function loadBackupList() {
        try {
            const response = await fetch('auto_save.php?action=get_backups');
            const backups = await response.json();
            
            const backupList = document.getElementById('backup-list');
            
            if (backups.length > 0) {
                let html = '<h4>Sauvegardes disponibles</h4><ul class="backup-items" role="list">';
                backups.forEach(backup => {
                    html += `
                        <li class="backup-item" role="listitem">
                            <div class="backup-info">
                                <strong>${backup.filename}</strong>
                                <small>${backup.created_formatted}</small>
                                <span class="backup-size">${formatFileSize(backup.size)}</span>
                            </div>
                            <div class="backup-actions">
                                <button onclick="restoreBackup('${backup.filepath}')" class="action-btn restore" aria-label="Restaurer la sauvegarde ${backup.filename}">Restaurer</button>
                                <button onclick="downloadBackup('${backup.filepath}')" class="action-btn download" aria-label="Télécharger la sauvegarde ${backup.filename}">Télécharger</button>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                backupList.innerHTML = html;
                
                document.getElementById('last-backup').textContent = backups[0].created_formatted;
            } else {
                backupList.innerHTML = '<p>Aucune sauvegarde disponible</p>';
            }
        } catch (error) {
            console.error('Erreur chargement sauvegardes:', error);
            showNotification('Erreur lors du chargement des sauvegardes', 'error');
        }
    }

    async function restoreBackup(backupFile) {
        if (confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Cela remplacera les données actuelles.')) {
            try {
                const response = await fetch(`auto_save.php?action=restore_backup&backup_file=${encodeURIComponent(backupFile)}`);
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Sauvegarde restaurée avec succès', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('Erreur lors de la restauration', 'error');
                }
            } catch (error) {
                showNotification('Erreur de connexion', 'error');
            }
        }
    }

    function downloadBackup(backupFile) {
        window.open(backupFile, '_blank');
    }

    // Fonctions d'historique
    async function loadHistory() {
        try {
            const response = await fetch('history.php?action=get_history&limit=50');
            const history = await response.json();
            
            const historyList = document.getElementById('history-list');
            
            if (history.length > 0) {
                let html = '<ul class="history-items" role="list">';
                history.forEach(entry => {
                    const actionIcon = getActionIcon(entry.action);
                    html += `
                        <li class="history-item" data-action="${entry.action}" role="listitem">
                            <div class="history-icon">${actionIcon}</div>
                            <div class="history-info">
                                <strong>${getActionLabel(entry.action)}</strong>
                                <p>${entry.details}</p>
                                <small>${new Date(entry.timestamp).toLocaleString('fr-FR')}</small>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                historyList.innerHTML = html;
            } else {
                historyList.innerHTML = '<p>Aucun historique disponible</p>';
            }
        } catch (error) {
            console.error('Erreur chargement historique:', error);
            showNotification('Erreur lors du chargement de l\'historique', 'error');
        }
    }

    function filterHistory() {
        const filter = document.getElementById('history-filter').value;
        const items = document.querySelectorAll('.history-item');
        
        items.forEach(item => {
            item.style.display = (!filter || item.dataset.action === filter) ? 'flex' : 'none';
        });
    }

    async function clearHistory() {
        if (confirm('Êtes-vous sûr de vouloir vider l\'historique ?')) {
            try {
                const response = await fetch('history.php?action=clear_history');
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Historique vidé', 'success');
                    loadHistory();
                } else {
                    showNotification('Erreur lors du vidage', 'error');
                }
            } catch (error) {
                showNotification('Erreur de connexion', 'error');
            }
        }
    }

    // Fonctions avancées
    async function validateXML() {
        try {
            const response = await fetch('validation.php?action=validate');
            const result = await response.json();
            
            const validationResult = document.getElementById('validation-result');
            
            if (result.valid) {
                validationResult.innerHTML = '<p class="success">✅ Fichier XML valide</p>';
            } else {
                let html = '<p class="error">❌ Erreurs de validation:</p><ul>';
                result.errors.forEach(error => {
                    html += `<li>${error}</li>`;
                });
                html += '</ul>';
                validationResult.innerHTML = html;
            }
        } catch (error) {
            document.getElementById('validation-result').innerHTML = '<p class="error">Erreur lors de la validation</p>';
            showNotification('Erreur lors de la validation XML', 'error');
        }
    }

    function optimizeXML() {
        if (confirm('Optimiser le fichier XML ? Cela peut prendre quelques secondes.')) {
            showNotification('Optimisation en cours...', 'info');
            fetch('optimize.php?action=optimize_xml')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('XML optimisé avec succès', 'success');
                    } else {
                        showNotification('Erreur lors de l\'optimisation', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion', 'error');
                });
        }
    }

    function exportData() {
        window.open('export.php', '_blank');
        showNotification('Exportation des données initiée', 'info');
    }

    // Fonctions utilitaires
    function getActionIcon(action) {
        const icons = {
            'send_message': '💬',
            'add_contact': '👤',
            'create_group': '👥',
            'update_contact': '✏️',
            'delete_contact': '🗑️',
            'switch_conversation': '🔄'
        };
        return icons[action] || '📝';
    }

    function getActionLabel(action) {
        const labels = {
            'send_message': 'Message envoyé',
            'add_contact': 'Contact ajouté',
            'create_group': 'Groupe créé',
            'update_contact': 'Contact modifié',
            'delete_contact': 'Contact supprimé',
            'switch_conversation': 'Conversation changée'
        };
        return labels[action] || action;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
    </script>
</body>
</html>