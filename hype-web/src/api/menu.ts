import { fetchAppData } from './app-data';

export const fetchMenu = async () => {
    try {
        const data = await fetchAppData();
        return data?.menu || [];
    } catch (error) {
        console.error('Menu API Error:', error);
        return [];
    }
};
