import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["count", "total", "message", "pageTotal", "lineSubtotal"];

    connect() {
        console.log("Contrôleur du panier connecté !");
        this.refreshSummary();
    }

    async add(event) {
        event.preventDefault();

        const url = event.currentTarget.getAttribute("href");

        try {
            const response = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });

            if (response.ok) {
                const data = await response.json();
                this.updateCartDisplay(data);
                this.showMessage(this.element.dataset.cartAddSuccess || "Produit ajouté au panier !");
            } else {
                this.showMessage(this.element.dataset.cartAddError || "Erreur lors de l’ajout au panier.");
            }
        } catch (e) {
            console.error(e);
            this.showMessage("Une erreur est survenue.");
        }
    }

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

    updateCartDisplay(data) {
        if (this.hasCountTarget) this.countTarget.textContent = data.count ?? "0";
        if (this.hasTotalTarget) this.totalTarget.textContent = (data.total ?? 0).toFixed(2) + " €";
        if (this.hasPageTotalTarget) this.pageTotalTarget.textContent = (data.total ?? 0).toFixed(2) + " €";
    }

    showMessage(text) {
        if (this.hasMessageTarget) {
            this.messageTarget.textContent = text;
            this.messageTarget.style.display = "block";
            this.messageTarget.classList.add("show");

            setTimeout(() => {
                this.messageTarget.classList.remove("show");
                this.messageTarget.style.display = "none";
            }, 2000);
        }
    }

    async remove(event) {
        event.preventDefault();
        const url = event.currentTarget.getAttribute("href");
        const row = event.currentTarget.closest("tr");
        const qtyInput = row ? row.querySelector('input[name="quantity"]') : null;
        const lineSubtotalEl = row ? row.querySelector("[data-cart-line-subtotal]") : null;
        const optimisticQty = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;
        const optimisticSubtotal = lineSubtotalEl
            ? parseFloat((lineSubtotalEl.textContent || "0").replace(/[^\d.,-]/g, "").replace(",", "."))
            : 0;
        try {
            const response = await fetch(url, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                credentials: "same-origin",
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.error) {
                this.showMessage(data.error || "Erreur lors de la suppression.");
                // Si pas de JSON valide, on laisse la page se recharger
                if (!response.ok && !data) window.location.href = url;
                return;
            }

            if (row) row.remove();

            const total =
                typeof data.total !== "undefined"
                    ? data.total
                    : Math.max(0, (this.hasPageTotalTarget ? parseFloat(this.pageTotalTarget.textContent) || 0 : 0) - optimisticSubtotal);
            const count =
                typeof data.count !== "undefined"
                    ? data.count
                    : Math.max(0, (this.hasCountTarget ? parseInt(this.countTarget.textContent || "0", 10) || 0 : 0) - optimisticQty);
            this.updateCartDisplay({ total, count });

            if (this.hasPageTotalTarget && document.querySelectorAll('[data-cart-line-subtotal]').length === 0) {
                this.pageTotalTarget.textContent = "0.00 €";
            }
            this.refreshSummary();

            this.showMessage("Produit retiré.");
        } catch (e) {
            console.error(e);
            this.showMessage("Une erreur est survenue.");
        }
    }

    async updateQuantity(event) {
        event.preventDefault();
        const form = event.target.closest("form");
        if (!form) return;
        const url = form.getAttribute("action");
        const formData = new FormData(form);
        if (event.target.name === "quantity") {
            formData.set("quantity", event.target.value);
        }

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.error) {
                this.showMessage(data.error || "Erreur lors de la mise à jour.");
                return;
            }

            const productId = data.productId;
            const subtotalEl =
                this.lineSubtotalTargets.find((el) => el.dataset.productId === String(productId)) ||
                document.querySelector(`[data-cart-line-subtotal="${productId}"]`);
            if (subtotalEl) {
                subtotalEl.textContent = (data.lineSubtotal ?? 0).toFixed(2) + " €";
            }

            this.updateCartDisplay(data);
            this.showMessage("Quantité mise à jour.");
        } catch (e) {
            console.error(e);
            this.showMessage("Une erreur est survenue.");
        }
    }
}
