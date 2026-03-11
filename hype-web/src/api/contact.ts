export const submitContactForm = async (formData: any) => {
    try {
        const res = await fetch('/api/wp/wcs/v1/form/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Submission failed');
        return data;
    } catch (error) {
        console.error('Contact Form Error:', error);
        throw error;
    }
};
