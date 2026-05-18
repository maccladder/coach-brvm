<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminWalletTopupMail;
use App\Mail\AdminWalletTransferMail;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Models\VirtualWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdminWalletController extends Controller
{
    public function userSearch(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::where(fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
            )
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::query()
            ->join('virtual_wallets', 'users.id', '=', 'virtual_wallets.user_id')
            ->select('users.*', 'virtual_wallets.balance as wallet_balance')
            ->withCount('virtualPositions')
            ->when($search, fn($q) => $q->where(fn($q2) =>
                $q2->where('users.name', 'like', "%{$search}%")
                   ->orWhere('users.email', 'like', "%{$search}%")
            ))
            ->orderByDesc('virtual_wallets.balance')
            ->paginate(30)
            ->withQueryString();

        $userIds = $users->pluck('id')->all();
        $lastTopups = VirtualWalletTransaction::selectRaw('user_id, MAX(created_at) as last_at')
            ->whereIn('user_id', $userIds)
            ->whereIn('type', ['topup', 'topup_admin'])
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        return view('admin.wallets.index', compact('users', 'lastTopups', 'search'));
    }

    public function show(User $user)
    {
        $wallet     = $user->virtualWallet;
        $positions  = $user->virtualPositions()->orderByDesc('qty')->get();
        $transactions = VirtualWalletTransaction::where('user_id', $user->id)
            ->with('creator')
            ->latest()
            ->take(100)
            ->get();

        return view('admin.wallets.show', compact('user', 'wallet', 'positions', 'transactions'));
    }

    public function topup(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'motif'  => ['nullable', 'string', 'max:255'],
        ]);

        $amount  = (int) $data['amount'];
        $motif   = $data['motif'] ?? null;
        $newBalance = 0;

        DB::transaction(function () use ($user, $amount, $motif, &$newBalance) {
            $wallet = VirtualWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            $wallet->increment('balance', $amount);
            $newBalance = $wallet->balance;

            VirtualWalletTransaction::create([
                'user_id' => $user->id,
                'type'    => 'topup_admin',
                'amount'  => $amount,
                'motif'   => $motif,
                'meta'    => ['source' => 'admin'],
            ]);
        });

        Mail::to($user->email)->send(new AdminWalletTopupMail($user, $amount, $newBalance, $motif));

        return back()->with('success', 'Portefeuille rechargé de ' . number_format($amount, 0, ',', ' ') . ' FCFA.');
    }

    public function transfer(Request $request, User $user)
    {
        $data = $request->validate([
            'dest_email' => ['required', 'email', 'exists:users,email'],
            'amount'     => ['required', 'integer', 'min:1'],
            'motif'      => ['nullable', 'string', 'max:255'],
        ]);

        $dest   = User::where('email', $data['dest_email'])->firstOrFail();
        $amount = (int) $data['amount'];
        $motif  = $data['motif'] ?? null;

        if ($dest->id === $user->id) {
            return back()->with('error', 'Le compte source et le compte destination sont identiques.');
        }

        try {
            DB::transaction(function () use ($user, $dest, $amount, $motif) {
                // Ensure dest wallet exists before locking
                VirtualWallet::firstOrCreate(['user_id' => $dest->id], ['balance' => 0]);

                // Lock both in consistent order to prevent deadlocks
                $wallets = VirtualWallet::whereIn('user_id', [$user->id, $dest->id])
                    ->orderBy('user_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('user_id');

                $sourceWallet = $wallets[$user->id] ?? null;

                if (!$sourceWallet) {
                    throw new \Exception('Le compte source ne possède pas de portefeuille.');
                }

                if ($sourceWallet->balance < $amount) {
                    throw new \Exception('Solde insuffisant sur le compte source (' . number_format($sourceWallet->balance, 0, ',', ' ') . ' FCFA disponibles).');
                }

                $destWallet = $wallets[$dest->id];
                $note       = $motif ? " – {$motif}" : '';

                $sourceWallet->decrement('balance', $amount);
                $destWallet->increment('balance', $amount);

                VirtualWalletTransaction::create([
                    'user_id' => $user->id,
                    'type'    => 'transfer_out',
                    'amount'  => -$amount,
                    'motif'   => "Transfert vers {$dest->name}{$note}",
                    'meta'    => ['source' => 'admin_transfer', 'dest_id' => $dest->id],
                ]);

                VirtualWalletTransaction::create([
                    'user_id' => $dest->id,
                    'type'    => 'transfer_in',
                    'amount'  => $amount,
                    'motif'   => "Transfert depuis {$user->name}{$note}",
                    'meta'    => ['source' => 'admin_transfer', 'source_id' => $user->id],
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $sourceBalance = VirtualWallet::where('user_id', $user->id)->value('balance');
        $destBalance   = VirtualWallet::where('user_id', $dest->id)->value('balance');

        Mail::to($user->email)->send(new AdminWalletTransferMail($user, $dest, $amount, (float) $sourceBalance, 'sent', $motif));
        Mail::to($dest->email)->send(new AdminWalletTransferMail($dest, $user, $amount, (float) $destBalance, 'received', $motif));

        return back()->with('success',
            'Transfert de ' . number_format($amount, 0, ',', ' ') . ' FCFA effectué de ' . $user->name . ' vers ' . $dest->name . '.'
        );
    }
}
