# Notes de déploiement — Boursiv
## Feature : email automatique "achat non abouti"

---

## 1. Sécurité Git — à lire avant tout push

`.env` est dans `.gitignore` et ne doit **jamais** être commité.  
Il contient les credentials Office365, Paystack, CinetPay, Claude API, etc.  
Seul `.env.example` part sur Git.

---

## 2. Procédure de déploiement en production (SSH)

### Étape 1 — Récupérer le code

```bash
git pull origin main
```

### Étape 2 — Appliquer la migration

Crée la colonne `abandoned_notified_at` sur la table `payments` :

```bash
php artisan migrate
```

Vérifier que la migration `2026_05_28_000001_add_abandoned_notified_at_to_payments` apparaît dans la liste.

### Étape 3 — Variables à vérifier/ajouter dans le `.env` de PROD

Ouvrir le `.env` du serveur (`nano .env` ou équivalent) et s'assurer que ces lignes sont présentes et correctes :

```dotenv
# Mail — prod envoie de vrais mails via Office365
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=noreply@coach-brvm.com
MAIL_PASSWORD=<mot_de_passe_prod>
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=noreply@coach-brvm.com
MAIL_FROM_NAME="Boursiv"

# Queue — les mails passent par le worker
QUEUE_CONNECTION=database

# WhatsApp support — bouton dans le mail "achat non abouti"
WHATSAPP_SUPPORT_NUMBER=2250767123451
```

> **MAIL_SCHEME — valeurs acceptées uniquement : `smtp` (port 587, STARTTLS auto) ou `smtps` (port 465, SSL).**
> Ne jamais mettre `tls` — ce n'est pas un scheme reconnu par Symfony Mailer (UnsupportedSchemeException).
> `MAIL_ENCRYPTION` est une clé obsolète en Laravel 11 ; la supprimer si elle est présente.

### Étape 4 — Recharger la configuration

Après toute modification du `.env` de prod :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Étape 5 — Worker Supervisor (OBLIGATOIRE — sans lui, aucun mail ne part)

Créer `/etc/supervisor/conf.d/coach-brvm-worker.conf` :

```ini
[program:coach-brvm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/coach-brvm/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/coach-brvm/storage/logs/worker.log
stopwaitsecs=3600
```

Adapter `/var/www/coach-brvm` au chemin réel du projet sur le serveur.

Activer et démarrer :

```bash
supervisorctl reread
supervisorctl update
supervisorctl start coach-brvm-worker:*
supervisorctl status
```

### Étape 6 — Cron scheduler (OBLIGATOIRE — sans lui, la commande ne tourne jamais)

Ajouter dans le crontab de `www-data` :

```bash
crontab -u www-data -e
```

Ligne à ajouter :

```cron
* * * * * cd /var/www/coach-brvm && php artisan schedule:run >> /dev/null 2>&1
```

Cela déclenche `payments:notify-abandoned` toutes les **5 minutes** (cadence définie dans `routes/console.php`).

---

## 3. Comment tester en prod sans envoyer de vrais mails à des clients

**Option A — Dry-run via tinker (aucun mail envoyé, lecture seule)**

Compte le nombre de paiements qui seraient ciblés :

```bash
php artisan tinker --execute="
echo App\Models\Payment::where('status','PENDING')
    ->whereNull('credited_at')
    ->whereNull('abandoned_notified_at')
    ->where('created_at','<=',now()->subMinutes(15))
    ->count() . ' paiements seraient notifiés';
"
```

**Option B — Tester le contenu du mail sans l'envoyer**

Basculer temporairement `MAIL_MAILER=log` dans le `.env` de prod, relancer `config:cache`, puis lancer le worker une fois :

```bash
php artisan queue:work --once
```

Le mail s'écrit dans `storage/logs/laravel.log`. Vérifier le sujet, le corps, le lien WhatsApp.
Remettre `MAIL_MAILER=smtp` + `config:cache` avant de démarrer Supervisor.

**Option C — Envoyer un mail de test vers sa propre adresse**

En tinker, cibler un payment spécifique déjà notifié (sans changer `abandoned_notified_at`) :

```bash
php artisan tinker --execute="
\$p = App\Models\Payment::find(<id>);
\$p->load('user');
\$mailer = app('mailer');
(new App\Mail\AbandonedPaymentMail(\$p))->to('maccladder@gmail.com')->send(\$mailer);
echo 'Envoyé.';
"
```

---

## 4. Checklist déploiement

- [ ] `git pull origin main` effectué
- [ ] `php artisan migrate` — colonne `abandoned_notified_at` créée
- [ ] `.env` prod mis à jour : `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtp`, `WHATSAPP_SUPPORT_NUMBER=2250767123451`
- [ ] `php artisan config:cache` relancé
- [ ] Supervisor configuré et worker démarré (`supervisorctl status` = RUNNING)
- [ ] Cron `schedule:run` actif (`crontab -l -u www-data`)
- [ ] Test dry-run : compter les paiements ciblés
- [ ] Test mail vers propre adresse pour valider le rendu en prod
