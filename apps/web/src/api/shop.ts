export const fetchShopProducts = async (params: Record<string, string> = {}) => {
    try {
        const queryString = new URLSearchParams(params).toString();
        const url = `/api/wp/wcs/v1/products?${queryString}`;

        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to fetch shop products');
        return await res.json();
    } catch (error) {
        console.error('Shop API Error:', error);
        return { products: [], total: 0, pages: 0 };
    }
};
