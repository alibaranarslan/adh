import './bootstrap';

document.addEventListener('alpine:init', () => {
    window.Alpine.store('darkMode', {
        on: localStorage.getItem('darkMode') === 'true',
        toggle() {
            this.on = !this.on;
            localStorage.setItem('darkMode', this.on ? 'true' : 'false');
            document.documentElement.classList.toggle('dark', this.on);
        },
        init() {
            if (localStorage.getItem('darkMode') !== 'true') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        },
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img[loading]').forEach(img => {
        img.addEventListener('error', function () {
            if (this.dataset.fallbackApplied) return;
            this.dataset.fallbackApplied = 'true';
            const wrapper = document.createElement('div');
            wrapper.className = 'w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800';
            wrapper.innerHTML = '<svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
            this.parentNode.replaceChild(wrapper, this);
        }, { once: true });
    });
});
