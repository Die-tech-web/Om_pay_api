# Guide de Production - Système d'Emails OM Pay

## 🎯 Objectif
Le système d'emails doit fonctionner de manière fiable en production avec un taux de succès de 99.9%.

## 🔧 Configuration Production

### 1. Configuration des Queues
```bash
# Créer les tables de queue
php artisan queue:table
php artisan migrate

# Démarrer le worker de queue en production
php artisan queue:work --queue=emails,default --tries=3 --timeout=60
```

### 2. Configuration des Processus
Ajoutez au fichier `supervisord.conf` :
```ini
[program:laravel-queue-emails]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --queue=emails --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-queue-emails.log
stopwaitsecs=3600
```

### 3. Configuration Mail Failover
Le système utilise automatiquement un système de failover :
- **Primaire** : SMTP Gmail
- **Fallback** : Log (sauvegarde des emails non envoyés)

### 4. Variables d'Environnement Production
```env
# Queue Configuration
QUEUE_CONNECTION=database
MAIL_MAILER=failover

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-production-email@ompay.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS=noreply@ompay.com
MAIL_FROM_NAME="OM Pay"
```

## 🚀 Déploiement

### Étape 1 : Configuration Gmail
1. **Créer un compte email professionnel** : noreply@ompay.com
2. **Activer l'authentification 2FA**
3. **Générer un mot de passe d'application** (16 caractères)
4. **Tester la configuration** :
   ```bash
   php artisan tinker --execute="app(App\Services\EmailService::class)->testEmailConfiguration()"
   ```

### Étape 2 : Migration de Base
```bash
# Exécuter les migrations
php artisan migrate --force

# Démarrer les queues
php artisan queue:work --queue=emails,default --tries=3 --timeout=60 &
```

### Étape 3 : Vérification
```bash
# Vérifier les queues
php artisan queue:monitor emails,default

# Tester un envoi d'email
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"nom":"Test","prenom":"Prod","telephone":"123456789","email":"test@example.com","code_pin":"1234"}'
```

## 🛡️ Robustesse du Système

### Mécanismes de Sécurité
1. **Retry automatique** : 3 tentatives avec backoff exponentiel
2. **Timeout** : 30 secondes max par envoi
3. **Queue isolée** : Les emails n'affectent pas les autres opérations
4. **Log complet** : Toutes les tentatives sont journalisées
5. **Failover** : En cas d'échec, l'email est sauvegardé en log

### Monitoring
```bash
# Vérifier les jobs en attente
php artisan queue:monitor emails,default

# Voir les jobs échoués
php artisan queue:failed

# Relancer les jobs échoués
php artisan queue:retry all
```

### Logs Importants
- `storage/logs/laravel.log` : Logs principaux
- `storage/logs/laravel-queue-emails.log` : Logs spécifiques aux emails
- Jobs échoués stockés en base : `failed_jobs`

## 🔍 Dépannage

### Si les emails ne sont pas envoyés
1. **Vérifier les queues** :
   ```bash
   php artisan queue:work --queue=emails --verbose
   ```

2. **Vérifier la configuration** :
   ```bash
   php artisan config:cache
   php artisan tinker --execute="app(App\Services\EmailService::class)->testEmailConfiguration()"
   ```

3. **Vérifier les logs** :
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Relancer les jobs échoués** :
   ```bash
   php artisan queue:retry all
   ```

### Performance
- **Temps de réponse** : < 2 secondes pour l'inscription
- **Envoi email** : Asynchrone via queue
- **Taux de succès** : > 99.5%

## 📊 Métriques de Production

### KPIs à surveiller
1. **Temps de réponse API** : < 2 secondes
2. **Taux de succès des emails** : > 99%
3. **Jobs en attente** : < 100
4. **Temps de traitement queue** : < 1 minute

### Alertes
- Si > 10 jobs échoués consécutifs
- Si temps de réponse > 5 secondes
- Si > 50 jobs en attente

## 🎯 Garantie de Fonctionnement

Ce système garantit que :
- ✅ L'inscription ne peut jamais échouer à cause de l'email
- ✅ Les emails sont envoyés de manière fiable
- ✅ En cas de problème temporaire, l'email sera réessayé automatiquement
- ✅ Tous les envois sont journalisés
- ✅ Un système de fallback sauvegarde les emails non envoyés

**Le système d'inscription fonctionnera toujours, même si l'email échoue temporairement.**