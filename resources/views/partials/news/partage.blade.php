{{-- resources/views/partials/news/partage.blade.php --}}
{{-- Boutons de partage d'un article : @include('partials.news.partage', ['news' => $n]) --}}
<div class="news-partage">
    <a href="{{ $news->lienWhatsApp() }}" target="_blank" rel="noopener"
       class="news-partage-btn news-partage-wa" aria-label="Partager « {{ $news->title }} » sur WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
    </a>
    <button type="button" class="news-partage-btn news-partage-copier"
            data-url="{{ route('news.show', $news->slug) }}" aria-label="Copier le lien de l'article">
        <i class="bi bi-link-45deg" aria-hidden="true"></i> <span>Copier le lien</span>
    </button>
</div>

@once
@push('styles')
<style>
    .news-partage { display: flex; flex-wrap: wrap; gap: 8px; }
    .news-partage-btn {
        display: inline-flex; align-items: center; gap: 6px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .06em; text-transform: uppercase; text-decoration: none;
        padding: 7px 12px; border-radius: 3px; cursor: pointer; transition: all .2s;
        background: transparent; border: 1px solid rgba(201,168,76,.3); color: #C9A84C;
    }
    .news-partage-btn:hover { border-color: #C9A84C; color: #C9A84C; }
    .news-partage-btn .bi { font-size: 14px; }
    .news-partage-wa { border-color: rgba(37,211,102,.45); color: #25D366; }
    .news-partage-wa:hover { background: rgba(37,211,102,.1); border-color: #25D366; color: #25D366; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.news-partage-copier');
        if (!btn) return;
        e.preventDefault();

        const url = btn.dataset.url;
        let ok = false;
        try {
            await navigator.clipboard.writeText(url);
            ok = true;
        } catch (err) {
            // Repli (http, anciens navigateurs)
            const ta = document.createElement('textarea');
            ta.value = url; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { ok = document.execCommand('copy'); } catch (e2) {}
            ta.remove();
        }

        const label = btn.querySelector('span');
        const avant = label.textContent;
        label.textContent = ok ? 'Lien copié' : url;
        setTimeout(() => { label.textContent = avant; }, 2000);
    });
</script>
@endpush
@endonce
