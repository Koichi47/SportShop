// js/cart.js - Управление корзиной
const Cart = {
    storageKey: 'sportshop_cart',
    
    get() {
        const data = localStorage.getItem(this.storageKey);
        return data ? JSON.parse(data) : [];
    },
    
    save(cart) {
        localStorage.setItem(this.storageKey, JSON.stringify(cart));
        this.updateCounter();
        return cart;
    },
    
    add(id, name, price, image = '') {
        let cart = this.get();
        const existing = cart.find(item => item.id == id);
        
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                id: parseInt(id),
                name: name,
                price: parseFloat(price),
                quantity: 1,
                image: image || 'https://via.placeholder.com/80'
            });
        }
        
        this.save(cart);
        this.showMessage(`✅ ${name} добавлен в корзину`);
        return cart;
    },
    
    update(id, quantity) {
        let cart = this.get();
        const index = cart.findIndex(item => item.id == id);
        
        if (index !== -1) {
            if (quantity <= 0) {
                cart.splice(index, 1);
            } else {
                cart[index].quantity = parseInt(quantity);
            }
        }
        
        this.save(cart);
        return cart;
    },
    
    remove(id) {
        let cart = this.get();
        const item = cart.find(item => item.id == id);
        cart = cart.filter(item => item.id != id);
        this.save(cart);
        if (item) {
            this.showMessage(`❌ ${item.name} удален из корзины`);
        }
        return cart;
    },
    
    clear() {
        this.save([]);
        this.showMessage('🧹 Корзина очищена');
        return [];
    },
    
    getCount() {
        const cart = this.get();
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    },
    
    getTotal() {
        const cart = this.get();
        return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },
    
    updateCounter() {
        const count = this.getCount();
        document.querySelectorAll('#cart-count').forEach(el => {
            if (el) el.innerText = count;
        });
    },
    
    showMessage(text, isError = false) {
        const toast = document.getElementById('toastMsg');
        if (toast) {
            toast.textContent = text;
            toast.style.background = isError ? '#dc3545' : '#28a745';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        } else {
            alert(text);
        }
    }
};

window.addToCart = (id, name, price, image) => Cart.add(id, name, price, image);
window.updateCartItem = (id, qty) => Cart.update(id, qty);
window.removeCartItem = (id) => Cart.remove(id);
window.clearCart = () => Cart.clear();

document.addEventListener('DOMContentLoaded', () => Cart.updateCounter());