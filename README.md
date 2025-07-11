# Projet WhatsApp XML

Une application web inspirée de WhatsApp, développée avec PHP, XML, JavaScript et CSS personnalisé. Elle permet de gérer des discussions, modifier des profils de contacts et de groupes, et ajouter de nouveaux contacts, avec les données stockées dans un fichier XML.

## Fonctionnalités
- **Interface de discussion** : Affiche les conversations avec les contacts et groupes, avec statut "Lu"/"Non lu" et "Moi" pour les messages de groupe envoyés par l'utilisateur (`expediteur="1"`).
- **Modification de profils** : Modifiez les détails des contacts (`prenom`, `nom`, `numero_telephone`, `photo_profile`, `status`) et des groupes (`nom_groupe`, `photo_groupe`, `admin`, `membres`) dans la barre latérale droite de `index.php`.
- **Ajout de contacts** : Ajoutez des contacts via `add_contact.php` avec un formulaire stylé.
- **Gestion des groupes** : Ajoutez/supprimez des membres et mettez à jour les paramètres des groupes.
- **Interface dynamique** : Met à jour les discussions et profils en temps réel via JavaScript.
- **Style personnalisé** : Utilise `style.css` pour une apparence cohérente, sans Bootstrap.

## Prérequis
- **XAMPP** : Apache et PHP activés (PHP 7.4+ recommandé).
- **Navigateur web** : Chrome, Firefox ou équivalent.
- **Permissions** : Rendre le dossier `images/` et `whatsapp.xml` inscriptibles.
- **Validation XML** : Un fichier `whatsapp.dtd` valide pour `whatsapp.xml`.

## Installation
1. **Installer XAMPP** :
   - Téléchargez et installez [XAMPP](https://www.apachefriends.org/).
   - Démarrez Apache depuis le panneau de contrôle XAMPP.

2. **Copier les fichiers** :
   - Placez les fichiers du projet dans `C:\xampp\htdocs\projet-xml\` (Windows) ou équivalent.
   - Fichiers requis :
     - `index.php` : Interface principale.
     - `add.php` : Gère l'envoi de messages, l'ajout de contacts et les mises à jour.
     - `add_contact.php` : Formulaire pour ajouter des contacts.
     - `style.css` : Styles personnalisés.
     - `script.js` : Scripts JavaScript pour l'interactivité.
     - `whatsapp.xml` : Stockage des données (contacts, groupes, messages).
     - `whatsapp.dtd` : Validation XML.
     - `settings.php` : Page de paramètres (optionnel).
     - `add_group.php` : Formulaire pour ajouter des groupes (optionnel).
     - `images/` : Dossier pour les photos (contient `image.png` et `group.png`).


4. **Accéder à l'application** :
   - Ouvrez un navigateur et allez à `http://localhost/projet-xml/index.php`.

## Structure des fichiers
```
projet-xml/
├── images/
│   ├── image.png          # Photo de profil par défaut
│   ├── group.png          # Photo de groupe par défaut
├── whatsapp.xml           # Données des contacts, groupes et messages
├── whatsapp.dtd           # Validation XML
├── index.php              # Interface principale avec liste des discussions, fenêtre de chat et barre de profil
├── add.php                # Traite les formulaires (messages, contacts, mises à jour)
├── add_contact.php        # Formulaire pour ajouter des contacts
├── add_group.php          # Formulaire pour ajouter des groupes (optionnel)
├── settings.php           # Page de paramètres (optionnel)
├── style.css              # Styles personnalisés
├── script.js              # Scripts pour les mises à jour dynamiques
```

## Utilisation
1. **Accéder à l'application** :
   - Rendez-vous sur `http://localhost/projet-xml/index.php`.
   - La barre latérale gauche affiche les contacts et groupes de `whatsapp.xml`.

