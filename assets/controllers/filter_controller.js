import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["category", "status", "results"];

    async updateFilters() {
        const params = new URLSearchParams();
        if (this.categoryTarget.value) params.append('category', this.categoryTarget.value);
        if (this.statusTarget.value) params.append('status', this.statusTarget.value);

        try {
            const response = await fetch(`/shop?${params.toString()}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            const html = await response.text();
            this.resultsTarget.innerHTML = html;
        } catch (e) {
            console.error("Erreur lors du filtrage :", e);
        }
    }
}
