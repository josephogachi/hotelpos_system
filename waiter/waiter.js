document.addEventListener("DOMContentLoaded", () => {
    const loadProducts = (categoryId = null) => {
        fetch(`fetch_products.php?category_id=${categoryId}`)
            .then(res => res.text())
            .then(data => {
                document.getElementById("product-list").innerHTML = data;
            });
    };

    // Initial load
    loadProducts();

    document.querySelectorAll(".category-tab").forEach(btn => {
        btn.addEventListener("click", () => {
            loadProducts(btn.dataset.id);
        });
    });

    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("add-to-order")) {
            const card = e.target.closest(".product-card");
            const name = card.dataset.name;
            const price = parseFloat(card.dataset.price);

            const item = document.createElement("div");
            item.innerHTML = `<p>${name} - Ksh ${price.toFixed(2)}</p>`;
            document.getElementById("receipt-items").appendChild(item);

            let total = parseFloat(document.getElementById("grand-total").innerText.replace("Ksh", "")) || 0;
            total += price;
            document.getElementById("grand-total").innerText = `Ksh ${total.toFixed(2)}`;
        }
    });

    document.getElementById("print-receipt").addEventListener("click", () => {
        window.print();
    });
});
