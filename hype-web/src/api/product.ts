export const fetchProductDetail = async (slug: string) => {
    try {
        const res = await fetch(`/api/wp/wcs/v1/products?slug=${slug}`, { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to fetch product');
        return await res.json();
    } catch (error) {
        console.error('Product API Error:', error);
        return null;
    }
};
