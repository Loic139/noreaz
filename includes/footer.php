</main>
<footer class="site-footer">
    <div class="container">
        <p>28<sup>e</sup> Giron des Jeunesses Sarinoises &mdash; 24-28 juin 2026 &mdash; Noréaz</p>
        <p style="margin-top:.4rem">
            <a href="https://noreaz2026.ch/" target="_blank" rel="noopener"
               style="color:rgba(255,255,255,.45);font-size:.8rem;transition:color .2s"
               onmouseover="this.style.color='rgba(255,255,255,.8)'"
               onmouseout="this.style.color='rgba(255,255,255,.45)'">
                noreaz2026.ch →
            </a>
        </p>
    </div>
</footer>
<script>
// Menu mobile
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.site-nav').classList.toggle('open');
});

// Bouton indice
function toggleHint(btn) {
    const hintEl = btn.nextElementSibling;
    if (hintEl.style.display === 'none') {
        hintEl.textContent = btn.dataset.hint;
        hintEl.style.display = 'block';
        btn.textContent = '🙈 Cacher l\'indice';
    } else {
        hintEl.style.display = 'none';
        btn.textContent = '💡 Voir un indice';
    }
}
</script>
</body>
</html>
