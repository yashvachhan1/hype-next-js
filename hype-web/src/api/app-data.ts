let appDataCache: any = null;
let appDataPromise: Promise<any> | null = null;

export const fetchAppData = async () => {
    // Return cached data immediately if available
    if (appDataCache) return appDataCache;

    // Return the ongoing promise if request is already in flight
    if (appDataPromise) return appDataPromise;

    // Start a new fetch
    appDataPromise = fetch('/api/wp/wcs/v1/app-data', { cache: 'no-store' })
        .then(res => {
            if (!res.ok) throw new Error('Failed to fetch App Data');
            return res.json();
        })
        .then(data => {
            appDataCache = data; // Store in memory cache
            appDataPromise = null; // Clear promise
            return data;
        })
        .catch(err => {
            console.error('App Data Error:', err);
            appDataPromise = null; // Clear promise on error so next time it retries
            return null; // Return null so components handle it gracefully
        });

    return appDataPromise;
};
