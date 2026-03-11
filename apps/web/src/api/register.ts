export const registerUser = async (userData: any) => {
    try {
        const res = await fetch('/api/wp/wcs/v1/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Registration failed');
        return data;
    } catch (error) {
        console.error('Register API Error:', error);
        throw error;
    }
};
