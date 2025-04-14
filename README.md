# Installation Wizard pour AdminLicence

Ce système d'installation permet de configurer facilement un projet PHP/Laravel avec une interface utilisateur intuitive et multilingue.

## Fonctionnalités

- Interface utilisateur moderne et responsive
- Support multilingue (Français, English, Español, Português, العربية, 中文, Русский)
- Détection automatique du type de projet (PHP, Laravel, React)
- Vérification de licence
- Configuration de la base de données avec test de connexion
- Création de compte administrateur
- Installation automatisée

## Prérequis

- PHP 7.4 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Base de données MySQL/MariaDB (pour les fonctionnalités de base de données)
- Composer (pour les projets Laravel)

## Installation

1. Placez le dossier `install` à la racine de votre projet
2. Accédez à l'URL de votre projet suivi de `/install` (ex: https://votredomaine.com/install)
3. Suivez les étapes de l'assistant d'installation

## Structure des fichiers

```
install/
├── index.php                # Point d'entrée principal
├── installed.lock           # Créé après installation complète
├── assets/                  # Ressources statiques
│   ├── css/
│   │   └── style.css        # Styles CSS
│   └── js/
│       └── script.js        # Scripts JavaScript
├── ajax/                    # Handlers AJAX
│   ├── detect_project.php   # Détection du type de projet
│   ├── install.php          # Installation finale
│   ├── save_admin.php       # Sauvegarde des infos admin
│   ├── test_database.php    # Test de connexion à la DB
│   └── verify_license.php   # Vérification de licence
├── includes/                # Fichiers inclus
│   ├── footer.php           # Pied de page
│   ├── functions.php        # Fonctions utilitaires
│   ├── header.php           # En-tête
│   └── language.php         # Gestion des langues
├── languages/               # Fichiers de traduction
│   ├── ar.php               # Arabe
│   ├── en.php               # Anglais
│   ├── es.php               # Espagnol
│   ├── fr.php               # Français
│   ├── pt.php               # Portugais
│   ├── ru.php               # Russe
│   └── zh.php               # Chinois
└── steps/                   # Étapes d'installation
    ├── step1.php            # Vérification de licence
    ├── step2.php            # Détection du projet
    ├── step3.php            # Configuration DB
    ├── step4.php            # Compte administrateur
    └── step5.php            # Installation finale
```

## Personnalisation

### Ajouter une nouvelle langue

1. Créez un nouveau fichier dans le dossier `languages/` (ex: `de.php` pour l'allemand)
2. Copiez le contenu d'un fichier de langue existant et traduisez les valeurs
3. Ajoutez la nouvelle langue dans le sélecteur de langue dans `includes/header.php`

### Modifier le processus d'installation

Les fonctions principales se trouvent dans `includes/functions.php`. Vous pouvez modifier :

- `verifyLicense()` pour changer la vérification de licence
- `detectProjectType()` pour ajouter la détection d'autres types de projets
- `createAdminUser()` pour personnaliser la création du compte administrateur

## Sécurité

Une fois l'installation terminée, le système crée un fichier `installed.lock` qui empêche l'accès à l'assistant d'installation. Pour des raisons de sécurité supplémentaires, il est recommandé de supprimer ou renommer le dossier `install` après une installation réussie.

## Support

Pour toute question ou assistance, veuillez contacter le support technique.

## Licence

Ce système d'installation est fourni sous licence propriétaire. L'utilisation non autorisée est strictement interdite.
