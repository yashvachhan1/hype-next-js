import { fetchAppData } from './app-data';

export const fetchNewArrivals = async () => {
    try {
        const data = await fetchAppData();
        return data?.new_arrivals || [];
    } catch (error) {
        console.error('New Arrivals API Error:', error);
        return [];
    }
};
