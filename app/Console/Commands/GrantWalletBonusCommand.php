<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VirtualWalletBonusService;
use Illuminate\Console\Command;

class GrantWalletBonusCommand extends Command
{
    protected $signature = 'promo:wallet-bonus
                            {--dry-run : Affiche les cibles sans effectuer aucune écriture en DB}
                            {--limit= : Limite le traitement à N users}
                            {--user-id= : Cible un user spécifique par son ID}';

    protected $description = 'Attribue le bonus 50 000 FCFA virtuels aux users éligibles (Segment A de la promo Mai 2026)';

    public function handle(VirtualWalletBonusService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit  = $this->option('limit')   ? (int) $this->option('limit')   : null;
        $userId = $this->option('user-id') ? (int) $this->option('user-id') : null;

        $this->info('🎁 Promo Wallet Bonus — Coach BRVM');
        $this->info('Cutoff inscription : ' . config('otp.eligibility_cutoff'));
        $this->info('Période active    : ' . config('otp.wallet_bonus_start') . ' → ' . config('otp.wallet_bonus_end'));
        $this->info('Montant bonus     : ' . number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ') . ' FCFA virtuels');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE — aucune écriture en DB');
            $this->newLine();
        }

        // Query de base : users éligibles
        $query = User::query()
            ->where('created_at', '<', config('otp.eligibility_cutoff'))
            ->whereNotNull('phone_verified_at')
            ->whereNull('wallet_bonus_claimed_at');

        if ($userId) {
            $query->where('id', $userId);
            $this->info("🎯 Cible : user_id={$userId} (test isolé)");
        }

        if ($limit) {
            $query->limit($limit);
            $this->info("📊 Limite : {$limit} users");
        }

        $totalEligible = $query->count();

        if ($totalEligible === 0) {
            $this->error('Aucun user éligible trouvé.');
            return self::SUCCESS;
        }

        $this->info("👥 Users éligibles : {$totalEligible}");
        $this->newLine();

        // Mode dry-run : afficher la liste, ne rien modifier
        if ($dryRun) {
            $this->table(
                ['ID', 'Nom', 'Email', 'Phone verified at', 'Created at'],
                $query->get(['id', 'name', 'email', 'phone_verified_at', 'created_at'])
                    ->map(fn ($u) => [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->phone_verified_at?->format('Y-m-d H:i'),
                        $u->created_at?->format('Y-m-d H:i'),
                    ])->toArray()
            );
            $this->newLine();
            $this->info('Dry run terminé. Aucune modification effectuée.');
            return self::SUCCESS;
        }

        // Mode réel : confirmation explicite (default : non)
        if (!$this->confirm("Confirmer l'attribution du bonus à {$totalEligible} user(s) ?", false)) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        // Exécution avec progress bar
        $bar = $this->output->createProgressBar($totalEligible);
        $bar->start();

        $stats = [
            'granted'                   => 0,
            'already_claimed'           => 0,
            'phone_not_verified'        => 0,
            'user_created_after_cutoff' => 0,
            'outside_promo_window'      => 0,
            'user_not_found'            => 0,
            'other'                     => 0,
        ];

        $errors = [];

        // Itération chunked pour ne pas saturer la mémoire (250+ users)
        $query->chunkById(50, function ($users) use ($service, $bar, &$stats, &$errors) {
            foreach ($users as $user) {
                try {
                    $result = $service->grant($user);
                    $reason = $result['reason'] ?? 'other';

                    if ($result['success']) {
                        $stats['granted']++;
                    } elseif (isset($stats[$reason])) {
                        $stats[$reason]++;
                    } else {
                        $stats['other']++;
                    }
                } catch (\Throwable $e) {
                    $stats['other']++;
                    $errors[] = "user_id={$user->id}: " . $e->getMessage();
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Bilan final
        $this->info('📊 Bilan');
        $this->table(
            ['Catégorie', 'Nombre'],
            [
                ['✅ Bonus accordés',        $stats['granted']],
                ['⏭️  Déjà crédités',         $stats['already_claimed']],
                ['📵 Phone non vérifié',      $stats['phone_not_verified']],
                ['📅 Inscrit après cutoff',   $stats['user_created_after_cutoff']],
                ['⏰ Hors période promo',     $stats['outside_promo_window']],
                ['❓ User introuvable',       $stats['user_not_found']],
                ['⚠️  Autres erreurs',         $stats['other']],
            ]
        );

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Erreurs rencontrées :');
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line('  - ' . $err);
            }
            if (count($errors) > 10) {
                $this->line('  ... et ' . (count($errors) - 10) . ' autres.');
            }
        }

        return self::SUCCESS;
    }
}
