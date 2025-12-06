import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'suggestions', 'loader'];
    static values = {
        liveUrl: String,
        suggestionsUrl: String,
        minChars: { type: Number, default: 2 },
        delay: { type: Number, default: 300 }
    };

    connect() {
        this.timeout = null;
        this.abortController = null;
        this.selectedIndex = -1;

        console.log('🚀 Live Search Controller connected');
        console.log('📍 Controller element:', this.element);
        console.log('🎯 Has input target?', this.hasInputTarget);
        console.log('🎯 Has results target?', this.hasResultsTarget);
        console.log('🎯 Has suggestions target?', this.hasSuggestionsTarget);
    }

    disconnect() {
        this.clearSearch();
    }

    // Méthode principale appelée à chaque frappe
    search(event) {
        console.log('⌨️ User typing...', this.inputTarget.value);

        const query = this.inputTarget.value.trim();
        this.selectedIndex = -1;

        // Si la requête est trop courte
        if (query.length < this.minCharsValue) {
            this.hideSuggestions();
            // Si vide, réinitialiser les résultats
            if (query.length === 0) {
                this.performLiveSearch();
            }
            return;
        }

        // Debouncing
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            console.log('🔍 Performing search for:', query);
            this.performLiveSearch();
            this.performSuggestions(query);
        }, this.delayValue);
    }

    // Recherche en temps réel (mise à jour de la liste)
    async performLiveSearch() {
        if (!this.hasResultsTarget) return;

        // Annuler la requête précédente
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        // Récupérer le formulaire - chercher dans les descendants car le contrôleur est le wrapper
        const form = this.element.querySelector('form');
        if (!form) {
            console.error('❌ Form not found!');
            return;
        }

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        this.showLoader();

        try {
            const response = await fetch(`${this.liveUrlValue}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                signal: this.abortController.signal
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            this.resultsTarget.innerHTML = html;
            console.log('✅ Results updated');
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('❌ Live search error:', error);
        } finally {
            this.hideLoader();
        }
    }

    // Autocomplétion (suggestions)
    async performSuggestions(query) {
        if (!this.hasSuggestionsTarget) return;

        try {
            const response = await fetch(`${this.suggestionsUrlValue}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            this.displaySuggestions(data);
            console.log('💡 Suggestions displayed:', data.length);
        } catch (error) {
            console.error('❌ Suggestions error:', error);
            this.hideSuggestions();
        }
    }

    // Afficher les suggestions
    displaySuggestions(products) {
        if (!products || products.length === 0) {
            this.hideSuggestions();
            return;
        }

        const query = this.inputTarget.value.trim();
        let html = '';

        products.forEach((product, index) => {
            const highlighted = this.highlightText(product.name, query);
            html += `
                <a href="${product.url}" 
                   class="autocomplete-item ${index === this.selectedIndex ? 'active' : ''}"
                   data-index="${index}">
                    <img src="${product.image}" 
                         alt="${product.name}" 
                         class="autocomplete-thumb"
                         style="width:25px;height:25px;object-fit:cover;border-radius:3px;">
                    <div class="autocomplete-info">
                        <div class="autocomplete-name">${highlighted}</div>
                        <div class="autocomplete-price">${product.price} €</div>
                    </div>
                </a>
            `;
        });

        this.suggestionsTarget.innerHTML = html;
        this.suggestionsTarget.classList.remove('d-none');
    }

    // Cacher les suggestions
    hideSuggestions() {
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.innerHTML = '';
            this.suggestionsTarget.classList.add('d-none');
        }
    }

    // Navigation au clavier
    navigate(event) {
        const suggestions = this.suggestionsTarget.querySelectorAll('.autocomplete-item');
        if (suggestions.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, suggestions.length - 1);
                this.updateActiveItem(suggestions);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateActiveItem(suggestions);
                break;
            case 'Enter':
                event.preventDefault();
                if (this.selectedIndex >= 0) {
                    suggestions[this.selectedIndex].click();
                }
                break;
            case 'Escape':
                this.hideSuggestions();
                this.inputTarget.blur();
                break;
        }
    }

    // Mettre à jour l'élément actif
    updateActiveItem(suggestions) {
        suggestions.forEach((item, index) => {
            item.classList.toggle('active', index === this.selectedIndex);
        });
    }

    // Highlight du texte recherché
    highlightText(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    // Afficher/cacher le loader
    showLoader() {
        if (this.hasLoaderTarget) {
            this.loaderTarget.classList.remove('d-none');
        }
    }

    hideLoader() {
        if (this.hasLoaderTarget) {
            this.loaderTarget.classList.add('d-none');
        }
    }

    // Nettoyer
    clearSearch() {
        clearTimeout(this.timeout);
        if (this.abortController) {
            this.abortController.abort();
        }
    }
}
