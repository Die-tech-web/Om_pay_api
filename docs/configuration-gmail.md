# Configuration Gmail pour OM Pay

## Étapes pour configurer l'envoi d'emails avec Gmail

### 1. Activer l'authentification à deux facteurs sur votre compte Gmail

1. Allez dans les paramètres de sécurité de votre compte Google
2. Activez l'authentification à deux facteurs si ce n'est pas déjà fait

### 2. Générer un mot de passe d'application

1. Dans les paramètres de sécurité, cherchez "Mots de passe d'application"
2. Cliquez sur "Générer un mot de passe d'application"
3. Sélectionnez "Mail" et "Autres (nom personnalisé)"
4. Entrez "OM Pay" comme nom
5. Copiez le mot de passe généré (16 caractères, sans espaces)

### 3. Configurer le fichier .env

Remplacez les valeurs suivantes dans votre fichier `.env` :

```env
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=mot-de-passe-application-16-caracteres
```

**Exemple :**
```env
MAIL_USERNAME=rama.diop@gmail.com
MAIL_PASSWORD=abcd1234efgh5678
```

### 4. Redémarrer le serveur Laravel

```bash
php artisan serve
```

### 5. Tester l'envoi d'email

Créez un nouvel utilisateur via l'API register. Un email de confirmation sera automatiquement envoyé avec les identifiants de connexion.

## Configuration actuelle dans .env

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com        # ← Remplacez par votre email
MAIL_PASSWORD=your-app-password           # ← Remplacez par votre mot de passe d'application
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@ompay.com"
MAIL_FROM_NAME="OM Pay"
```

## Important

- **NE PAS** utiliser votre mot de passe Gmail normal
- Utilisez uniquement le mot de passe d'application généré
- Le mot de passe d'application est composé de 16 caractères sans espaces
- Conservez ce mot de passe en sécurité

## Dépannage

Si les emails ne sont pas envoyés :

1. Vérifiez que l'authentification à deux facteurs est activée
2. Vérifiez que vous utilisez le bon mot de passe d'application
3. Vérifiez les logs Laravel : `tail -f storage/logs/laravel.log`
4. Testez la configuration : `php artisan tinker --execute="Mail::raw('Test email', function(\$msg) { \$msg->to('test@example.com')->subject('Test'); });"</code>