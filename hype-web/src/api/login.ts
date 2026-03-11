export const loginUser = async (credentials: any) => {
    try {
        const res = await fetch('/api/wp/wcs/v1/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(credentials)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Login failed');
        return data; // { success: true, user: {...} }
    } catch (error) {
        console.error('Login API Error:', error);
        throw error;
    }
};
