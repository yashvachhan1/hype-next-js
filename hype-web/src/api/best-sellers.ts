import { fetchAppData } from './app-data';

export const fetchBestSellers = async () => {
    try {
        const data = await fetchAppData();
        return data?.best_sellers || [];
    } catch (error) {
        console.error('Best Sellers API Error:', error);
        return [];
    }
};
