export const processCheckout = async (orderData: any) => {
    try {
        const res = await fetch('/api/wp/wcs/v1/checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Checkout failed');
        return data;
    } catch (error) {
        console.error('Checkout API Error:', error);
        throw error; // Rethrow to handle in UI
    }
};
