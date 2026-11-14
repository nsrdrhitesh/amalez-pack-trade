export default function registerSite(Alpine) {
    Alpine.data('siteExperience', (measurement, placement) => ({
        showConsent: false, analyticsLoaded: false,
        init() { let consent; try { consent = localStorage.getItem('packtrade-consent'); } catch {} this.showConsent = !consent; if (consent === 'all') this.loadAnalytics(); },
        setConsent(value) { try { localStorage.setItem('packtrade-consent', value); } catch {} this.showConsent = false; if (value === 'all') this.loadAnalytics(); else if (this.analyticsLoaded) { window['ga-disable-' + measurement] = true; } },
        loadAnalytics() {
            if (this.analyticsLoaded || !/^G-[A-Z0-9]{4,20}$/.test(measurement || '')) return;
            window['ga-disable-' + measurement] = false;
            const script = document.createElement('script'); script.async = true; script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurement);
            (placement === 'footer' ? document.body : document.head).append(script);
            window.dataLayer = window.dataLayer || []; window.gtag = function () { window.dataLayer.push(arguments); }; window.gtag('js', new Date()); window.gtag('config', measurement); this.analyticsLoaded = true;
        },
    }));
    Alpine.data('imageGallery', (count) => ({
        count, index: 0, playing: false, paused: false, timer: null,
        init() { this.timer = setInterval(() => { if (this.playing && !this.paused && !document.hidden && !matchMedia('(prefers-reduced-motion: reduce)').matches) this.next(); }, 5000); },
        destroy() { clearInterval(this.timer); }, next() { this.index = (this.index + 1) % this.count; }, previous() { this.index = (this.index - 1 + this.count) % this.count; },
    }));
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-copy-url]'); if (!button) return;
        const status = button.parentElement.querySelector('.site-copy-status');
        try { await navigator.clipboard.writeText(button.dataset.copyUrl); status.textContent = 'Link copied.'; } catch { status.textContent = 'Copy the page URL from your address bar.'; }
    });
}
