document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('salesForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const priceInput = document.getElementById('price');
            const qtyInput = document.getElementById('qty');
            
            if (parseFloat(priceInput.value) <= 0 || parseInt(qtyInput.value) <= 0) {
                alert('Price and Quantity must be greater than zero.');
                e.preventDefault();
            }
        });
    }
});