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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .settings-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            width: 900px;
            height: 900px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .settings-header {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .settings-header h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 400;
        }
        .back-button {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        .settings-navigation {
            background: #f8f9fa;
            padding: 10px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .nav-tab {
            background: none;
            border: none;
            color: #333;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin: 5px 0;
        }
        .nav-tab:hover {
            background: #e0f7fa;
        }
        .nav-tab.active {
            background: #25D366;
            color: #fff;
        }
        .tab-icon {
            font-size: 18px;
        }
        .tab-count {
            background: #25D366;
            color: #fff;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: auto;
        }
        .nav-tab.active .tab-count {
            background: #fff;
            color: #25D366;
        }
        .settings-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px;
            background: #fff;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .panel-header h3 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .search-box {
            position: relative;
            width: 300px;
        }
        .modern-search {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .modern-search:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 5px rgba(37, 211, 102, 0.3);
        }
        .search-box::before {
            content: '🔍';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .modern-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-btn.primary {
            background: #25D366;
            color: #fff;
        }
        .modern-btn.primary:hover {
            background: #20b058;
            transform: translateY(-2px);
        }
        .contacts-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .contact-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .contact-card:hover {
            background: #e0f7fa;
            transform: translateY(-2px);
        }
        .avatar-container {
            position: relative;
            margin-right: 15px;
        }
        .contact-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        .status-dot.online {
            background: #25D366;
        }
        .status-dot.offline {
            background: #999;
        }
        .contact-info {
            flex-grow: 1;
        }
        .contact-name {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
        .contact-phone, .contact-status {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }
        .card-actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 50%;
        }
        .action-btn:hover {
            background: #25D366;
            color: #fff;
        }
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        .group-card {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 180px;
        }
        .group-card:hover {
            background: #e0f7fa;
            transform: translateY(-2px);
        }
        .group-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .group-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #25D366;
            color: #fff;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
            border: 2px solid #fff;
        }
        .group-info {
            margin-top: 10px;
        }
        .group-name {
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: 500;
            color: #333;
            word-wrap: break-word;
        }
        .group-members, .group-admin {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0;
        }
        .advanced-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .setting-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .setting-header {
            margin-bottom: 10px;
        }
        .setting-header h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .modern-switch {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .switch-slider {
            position: relative;
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .switch-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }
        input[type="checkbox"]:checked + .switch-slider {
            background: #25D366;
        }
        input[type="checkbox"]:checked + .switch-slider::before {
            transform: translateX(20px);
        }
        .switch-label {
            font-size: 14px;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .stat-card.primary {
            background: #e0f7fa;
        }
        .stat-card.success {
            background: #d4edda;
        }
        .stat-card.warning {
            background: #fff3cd;
        }
        .stat-icon {
            font-size: 24px;
        }
        .stat-number {
            font-size: 18px;
            font-weight: 500;
            color: #333;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s ease;
        }
        .toast-notification.success {
            background: #25D366;
        }
        .toast-notification.error {
            background: #e53935;
        }
        .toast-notification.info {
            background: #0288d1;
        }
        .toast-notification.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="settings-container" role="main">
        <div class="settings-header">
            <h2 aria-label="Paramètres"><i class="fas fa-cog"></i> Paramètres</h2>
            <a href="index.php" class="back-button" aria-label="Retour à la page de chat"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
        <nav class="settings-navigation" role="tablist">
            <button class="nav-tab active" onclick="showTab('contacts')" data-icon="contacts" role="tab" aria-selected="true" aria-controls="contacts-tab">
                <span class="tab-icon"><i class="fas fa-users"></i></span>
                <span class="tab-text">Contacts</span>
                <span class="tab-count"><?php echo count($xml->discussions->contacts->contact); ?></span>
            </button>
            <button class="nav-tab" onclick="showTab('groups')" data-icon="groups" role="tab" aria-selected="false" aria-controls="groups-tab">
                <span class="tab-icon"><i class="fas fa-user-friends"></i></span>
                <span class="tab-text">Groupes</span>
                <span class="tab-count"><?php echo count($xml->discussions->groupes->groupe); ?></span>
            </button>
            <button class="nav-tab" onclick="showTab('parameter')" data-icon="advanced" role="tab" aria-selected="false" aria-controls="parameter-tab">
                <span class="tab-icon"><i class="fas fa-cogs"></i></span>
                <span class="tab-text">Paramètres avancés</span>
            </button>
        </nav>
        <div class="settings-content">
            <section id="contacts-tab" class="tab-panel active" role="tabpanel" aria-labelledby="contacts">
                <header class="panel-header">
                    <h3><i class="fas fa-users"></i> Gestion des contacts</h3>
                    <div class="panel-actions">
                        <div class="search-box">
                            <input type="text" id="contact-search" placeholder="Rechercher un contact..." class="modern-search" aria-label="Rechercher un contact">
                        </div>
                        <a href="add_contact.php" class="modern-btn primary" aria-label="Ajouter un nouveau contact">
                            <span><i class="fas fa-user-plus"></i></span> Nouveau contact
                        </a>
                    </div>
                </header>
                <div class="stats-grid" role="region" aria-label="Statistiques des contacts">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo count($xml->discussions->contacts->contact); ?></span>
                            <span class="stat-label">Contacts total</span>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-circle"></i></div>
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
                        <div class="stat-icon"><i class="fas fa-circle"></i></div>
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
                        if (!file_exists($photo) || empty($photo)) {
                            $photo = 'images/default-avatar.png';
                        }
                        echo "
                        <div class='contact-card' data-contact-name='$fullName' role='listitem'>
                            <div class='avatar-container'>
                                <img src='$photo' alt='Photo de profil de $fullName' class='contact-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjUiIGZpbGw9IiNFNUU3RUIiLz4KPHBhdGggZD0iTTE4Ljc1IDE4Ljc1QzE4Ljc1IDE0LjIyNjIgMjEuOTc2MiAxMSAyNS4yNSA4Ljc1QzI4LjUyMzggMTEgMzEuNzUgMTQuMjI2MiAzMS43NSAxOC43NUMzMS43NSAyMy4yNzM4IDI4LjUyMzggMjYuNSAyNS4yNSAyNi41QzIxLjk3NjIgMjYuNSAxOC43NSAyMy4yNzM4IDE4Ljc1IDE4Ljc1WiIgZmlsbD0iIzlDOUM5OSIvPgo8cGF0aCBkPSJNMzEuNjI1IDMzLjEyNUMzMC40MDYzIDMxLjI2ODggMjguMDE1NiAyOS43NSAyNS4yNSAyOS43NUMyMi40ODQ0IDI5Ljc1IDIwLjA5MzggMzEuMjY4OCAxOC44NzUgMzMuMTI1QzE4LjUzMTMgMzMuNjg3NSAxOC43NDY5IDM0LjQzNzUgMTkuMzEyNSAzNC40Mzc1SDMxLjE4NzVDMzEuNzUzMSAzNC40Mzc1IDMxLjk2ODggMzMuNjg3NSAzMS42MjUgMzMuMTI1WiIgZmlsbD0iIzlDOUM5OSIvPgo8L3N2Zz4K'\">
                                <div class='status-dot $statusClass' aria-label='Statut $status'></div>
                            </div>
                            <div class='contact-info'>
                                <h4 class='contact-name'>$fullName</h4>
                                <p class='contact-phone'><i class='fas fa-phone'></i> $numero</p>
                                <span class='contact-status $statusClass'>$status</span>
                            </div>
                            <div class='card-actions'>
                                <button onclick='editContact($contactId)' class='action-btn edit' title='Modifier le contact' aria-label='Modifier $fullName'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button onclick='viewMessages($contactId)' class='action-btn view' title='Voir les messages' aria-label='Voir les messages de $fullName'>
                                    <i class='fas fa-comment'></i>
                                </button>
                                <button onclick='deleteContact($contactId)' class='action-btn delete' title='Supprimer le contact' aria-label='Supprimer $fullName'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </div>
                        </div>";
                    }
                    ?>
                </div>
            </section>
            <section id="groups-tab" class="tab-panel" role="tabpanel" aria-labelledby="groups">
                <header class="panel-header">
                    <h3><i class="fas fa-user-friends"></i> Gestion des groupes</h3>
                    <div class="panel-actions">
                        <div class="search-box">
                            <input type="text" id="group-search" placeholder="Rechercher un groupe..." class="modern-search" aria-label="Rechercher un groupe">
                        </div>
                        <a href="add_group.php" class="modern-btn primary" aria-label="Créer un nouveau groupe">
                            <span><i class="fas fa-users"></i></span> Nouveau groupe
                        </a>
                    </div>
                </header>
                <div class="stats-grid" role="region" aria-label="Statistiques des groupes">
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
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
                        $membersCount = count($groupe->membres->membre);
                        $adminContact = $xml->xpath("//contact[@id='$adminId']")[0];
                        $adminName = $adminContact ? (string)$adminContact->prenom . ' ' . (string)$adminContact->nom : 'Inconnu';
                        if (!file_exists($photo) || empty($photo)) {
                            $photo = 'images/default-group.png';
                        }
                        echo "
                        <div class='group-card' data-group-name='$nomGroupe' role='listitem'>
                            <div class='card-header'>
                                <div class='avatar-container'>
                                    <img src='$photo' alt='Photo du groupe $nomGroupe' class='group-avatar' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjUiIGZpbGw9IiNFNUU3RUIiLz4KPHBhdGggZD0iTTI4LjEyNSAxMC42MjVDMjkuMjM0NCAxMC42MjUgMzAuMTI1IDExLjUxNTYgMzAuMTI1IDEyLjYyNVYyNC4zNzVDMzAuMTI1IDI1LjQ4NDQgMjkuMjM0NCAyNi4zNzUgMjguMTI1IDI2LjM3NUgxNi4zNzVDMTUuMjY1NiAyNi4zNzUgMTQuMzc1IDI1LjQ4NDQgMTQuMzc1IDI0LjM3NVYxMi42MjVDMTQuMzc1IDExLjUxNTYgMTUuMjY1NiAxMC42MjUgMTYuMzc1IDEwLjYyNUgyOC4xMjVaTTE2LjM3NSAxMi42MjVWMjQuMzc1SDI4LjEyNVYxMi42MjVIMTYuMzc1Wk0yMC4zMTI1IDE2LjU2MjVIMjQuMzEyNVYxOC41NjI1SDIwLjMxMjVWMTYuNTYyNVpNMjAuMzEyNSAyMC41NjI1SDI0LjMxMjVWMjIuNTYyNUgyMC4zMTI1VjIwLjU2MjVaIiBmaWxsPSIjOUM5Qzk5Ii8+Cjwvc3ZnPgo='\">
                                    <div class='group-badge'>$membersCount</div>
                                </div>
                                <div class='group-info'>
                                    <p class='group-members'>$membersCount membres</p>
                                    <span class='group-admin'>Admin: $adminName</span>
                                </div>
                            </div>
                            <h4 class='group-name'>$nomGroupe</h4>
                            <div class='card-actions'>
                                <button onclick='editGroup($groupeId)' class='action-btn edit' title='Modifier le groupe' aria-label='Modifier $nomGroupe'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button onclick='viewGroupMessages($groupeId)' class='action-btn view' title='Voir les messages' aria-label='Voir les messages du groupe $nomGroupe'>
                                    <i class='fas fa-comment'></i>
                                </button>
                                <button onclick='deleteGroup($groupeId)' class='action-btn delete' title='Supprimer le groupe' aria-label='Supprimer $nomGroupe'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </div>
                        </div>";
                    }
                    ?>
                </div>
            </section>
            <section id="parameter-tab" class="tab-panel" role="tabpanel" aria-labelledby="parameter">
                <header class="panel-header">
                    <h3><i class="fas fa-cogs"></i> Paramètres avancés</h3>
                </header>
                <div class="advanced-grid" role="region" aria-label="Paramètres avancés">
                    <div class="setting-card">
                        <div class="setting-header">
                            <h4><i class="fas fa-bell"></i> Notifications</h4>
                        </div>
                        <div class="setting-content">
                            <label class="modern-switch">
                                <input type="checkbox" id="notifications-enabled" checked aria-label="Activer ou désactiver les notifications">
                                <span class="switch-slider"></span>
                                <span class="switch-label">Notifications en temps réel</span>
                            </label>
                            <label class="modern-switch">
                                <input type="checkbox" id="sound-enabled" checked aria-label="Activer ou désactiver le son des notifications">
                                <span class="switch-slider"></span>
                                <span class="switch-label">Son des notifications</span>
                            </label>
                        </div>
                    </div>
                    <div class="setting-card">
                        <div class="setting-header">
                            <h4><i class="fas fa-paint-brush"></i> Apparence</h4>
                        </div>
                        <div class="setting-content">
                            <label>
                                <span class="switch-label">Thème</span>
                                <select id="theme-selector" aria-label="Sélectionner le thème de l'application">
                                    <option value="light">Clair</option>
                                    <option value="dark">Sombre</option>
                                    <option value="system">Système</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeSettings();
        initializeSearch();
        initializeTabNavigation();
        initializeTheme();
    });

    function initializeSettings() {
        const notificationsEnabled = localStorage.getItem('notifications-enabled') !== 'false';
        const soundEnabled = localStorage.getItem('sound-enabled') !== 'false';
        
        document.getElementById('notifications-enabled').checked = notificationsEnabled;
        document.getElementById('sound-enabled').checked = soundEnabled;
        
        document.getElementById('notifications-enabled').addEventListener('change', function() {
            localStorage.setItem('notifications-enabled', this.checked);
            showNotification(this.checked ? 'Notifications activées' : 'Notifications désactivées', this.checked ? 'success' : 'info');
        });
        
        document.getElementById('sound-enabled').addEventListener('change', function() {
            localStorage.setItem('sound-enabled', this.checked);
            showNotification(this.checked ? 'Son activé' : 'Son désactivé', this.checked ? 'success' : 'info');
        });
    }

    function initializeTheme() {
        const themeSelector = document.getElementById('theme-selector');
        const savedTheme = localStorage.getItem('theme') || 'light';
        themeSelector.value = savedTheme;
        applyTheme(savedTheme);
        
        themeSelector.addEventListener('change', function() {
            const theme = this.value;
            localStorage.setItem('theme', theme);
            applyTheme(theme);
            showNotification(`Thème ${theme} appliqué`, 'success');
        });
    }

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
            document.querySelector('.settings-container').style.background = '#121212';
            document.querySelector('.settings-content').style.background = '#1e1e1e';
            document.querySelectorAll('.contact-card, .group-card, .stat-card, .setting-card').forEach(card => {
                card.style.background = '#2a2a2a';
                card.style.color = '#fff';
            });
        } else {
            document.body.classList.remove('dark-theme');
            document.querySelector('.settings-container').style.background = '#fff';
            document.querySelector('.settings-content').style.background = '#fff';
            document.querySelectorAll('.contact-card, .group-card, .stat-card, .setting-card').forEach(card => {
                card.style.background = '';
                card.style.color = '';
            });
        }
    }

    function initializeTabNavigation() {
        const tabs = document.querySelectorAll('.nav-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
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
        document.querySelectorAll('.tab-panel').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll('.nav-tab').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        const selectedTab = document.getElementById(tabName + '-tab');
        selectedTab.classList.add('active');
        selectedTab.setAttribute('aria-hidden', 'false');
        const selectedButton = document.querySelector(`.nav-tab[aria-controls="${tabName}-tab"]`);
        selectedButton.classList.add('active');
        selectedButton.setAttribute('aria-selected', 'true');
        selectedButton.focus();
        selectedTab.style.opacity = '0';
        selectedTab.style.transition = 'opacity 0.3s ease';
        setTimeout(() => selectedTab.style.opacity = '1', 50);
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

    function editContact(contactId) {
        window.location.href = `index.php?edit_contact=${contactId}`;
    }

    function deleteContact(contactId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
            fetch('add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    function editGroup(groupId) {
        window.location.href = `index.php?edit_group=${groupId}`;
    }

    function deleteGroup(groupId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?')) {
            fetch('add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
