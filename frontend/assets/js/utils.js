
function showMessage(message, type = "info") {
    const div = document.createElement("div");
    div.className = `alert alert-${type}`;
    div.innerText = message;

    document.body.prepend(div);

    setTimeout(() => div.remove(), 3000);
}

function setLoading(button, isLoading) {
    if (!button) return;

    button.disabled = isLoading;
    button.innerText = isLoading ? "Loading..." : button.dataset.originalText || "Submit";
}

function formatCurrency(amount) {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD"
    }).format(amount);
}