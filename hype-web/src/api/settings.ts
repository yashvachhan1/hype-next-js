import { fetchAppData } from './app-data';

export const fetchSettings = async () => {
    try {
        const data = await fetchAppData();
        return data || {}; // Returns rules, logo, etc.
    } catch (error) {
        console.error('Settings API Error:', error);
        return {};
    }
};
