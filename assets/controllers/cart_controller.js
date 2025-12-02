import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["count", "total", "message"];

    connect() {
        console.log("Contrôleur du panier connecté !");
        this.refreshSummary();
    }

    /**
     * Ajoute un produit au panier en AJAX
     */
    async add(event) {
        event.preventDefault();

        const url = event.currentTarget.getAttribute("href");

        try {
            const response = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });

            if (response.ok) {
                const data = await response.json();
                this.updateCartDisplay(data);
                this.showMessage("Produit ajouté au panier !");
            } else {
                this.showMessage("Erreur lors de l’ajout au panier.");
            }
        } catch (e) {
            console.error(e);
            this.showMessage("Une erreur est survenue.");
        }
    }

    /**
     * Récupère le résumé du panier (count/total) au chargement
     */
    async refreshSummary() {
        try {
            const response = await fetch("/shop/cart/summary", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.updateCartDisplay(data);
        } catch (e) {
            console.error("Erreur lors de la récupération du résumé panier", e);
        }
    }

    /**
     * Met à jour l’affichage du panier (nombre et total)
     */
    updateCartDisplay(data) {
        if (this.hasCountTarget) this.countTarget.textContent = data.count ?? "0";
        if (this.hasTotalTarget) this.totalTarget.textContent = (data.total ?? 0).toFixed(2) + " €";
    }

    /**
     * Affiche un petit message temporaire (succès ou erreur)
     */
    showMessage(text) {
        if (this.hasMessageTarget) {
            this.messageTarget.textContent = text;
            this.messageTarget.classList.add("show");

            setTimeout(() => {
                this.messageTarget.classList.remove("show");
            }, 2000);
        }
    }
}
