import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Store global supaya sidebar & navbar bisa saling bayar
Alpine.store('sidebar', {
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
});

Alpine.start();
