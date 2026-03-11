export const fetchCart = async () => {
    try {
        // Fallback to standard WooCommerce Store API since V4 snippet removed custom cart endpoint
        const res = await fetch('/api/wp/wc/store/v1/cart', { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to fetch cart');
        return await res.json();
    } catch (error) {
        console.error('Cart API Error:', error);
        return { items: [], total: "0.00", count: 0 };
    }
};

export const addToCart = async (productId: number, quantity: number = 1) => {
    try {
        // Fallback to standard WooCommerce Store API add to cart
        const res = await fetch('/api/wp/wc/store/v1/cart/add-item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, quantity }) // Note: WC Store API uses 'id', not 'product_id'
        });
        return await res.json();
    } catch (error) {
        console.error('Add to Cart Error:', error);
        throw error;
    }
};
