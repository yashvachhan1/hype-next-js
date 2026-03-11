export const fetchAccountData = async () => {
    try {
        const res = await fetch('/api/wp/wcs/v1/account', { cache: 'no-store' });
        if (res.status === 401) return null; // Not logged in
        if (!res.ok) throw new Error('Failed to fetch account data');
        return await res.json();
    } catch (error) {
        console.error('Account API Error:', error);
        return null;
    }
};
