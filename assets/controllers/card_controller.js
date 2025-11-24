import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    addCard(event) {
        event.preventDefault()
        const form = event.target

        fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cardList = document.querySelector("#card-list")
                    const newCard = document.createElement("div")
                    newCard.classList.add("col-md-4")
                    newCard.innerHTML = `
                        <div class="card bg-secondary border-info shadow text-center p-3">
                            <h5>**** **** **** ${data.card.number}</h5>
                            <p>Expiration : ${data.card.expirationDate}</p>
                        </div>
                    `
                    cardList.prepend(newCard)
                    form.reset()
                }
            })
    }
}
