import Alpine from 'alpinejs';
Alpine.data('packagingSite', () => ({
    active: 'home', filter: 'All formats', category: '', selected: null, summary: '',
    products: [], observer: null, brand: '',
    init() {
        this.brand = document.querySelector('meta[property="og:site_name"]')?.content || 'Amlez Pack Trade';
        this.products = JSON.parse(document.getElementById('packaging-products')?.textContent || '[]');
        this.observer = new IntersectionObserver(entries => {
            for (const entry of entries) if (entry.isIntersecting) this.active = entry.target.id;
        }, { rootMargin: '-15% 0px -60% 0px', threshold: 0 });
        document.querySelectorAll('main section[id]').forEach(section => this.observer.observe(section));
    },
    destroy() { this.observer?.disconnect(); },
    showProduct(id) {
        this.selected = this.products.find(product => product.id === id) || null;
        if (this.selected) document.getElementById('product-dialog').showModal();
    },
    closeProduct() {
        document.getElementById('product-dialog')?.close();
        this.selected = null;
    },
    prepareSummary(event) {
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const data = new FormData(form);
        const category = form.querySelector('select').selectedOptions[0].textContent;
        this.summary = [
            this.brand.toUpperCase() + ' — REQUIREMENT BRIEF', 'Prepared locally. Not submitted.', '',
            'Name: ' + data.get('full_name'), 'Company: ' + (data.get('company_name') || 'Not provided'),
            'Email: ' + data.get('email'), 'Phone: ' + (data.get('phone') || 'Not provided'),
            'Format: ' + category, '', 'Requirement:', data.get('details'),
        ].join('\n');
        this.$nextTick(() => form.querySelector('.pack-summary').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'nearest' }));
    },
    downloadBrief() {
        if (!this.summary) return;
        const url = URL.createObjectURL(new Blob([this.summary], { type: 'text/plain;charset=utf-8' }));
        const anchor = document.createElement('a');
        anchor.href = url; anchor.download = 'amlez-pack-trade-requirement.txt';
        document.body.appendChild(anchor); anchor.click(); anchor.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    },
}));
window.Alpine = Alpine;
Alpine.start();
