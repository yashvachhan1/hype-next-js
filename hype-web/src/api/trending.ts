import { fetchAppData } from './app-data';

export const fetchTrendingProducts = async () => {
    try {
        const data = await fetchAppData();
        return data?.trending || [];
    } catch (error) {
        console.error('Trending API Error:', error);
        return [];
    }
};
