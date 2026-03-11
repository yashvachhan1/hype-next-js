'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function LoginPage() {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const router = useRouter();

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const res = await fetch('/api/wp/wcs/v1/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include', // CRITICAL: This captures the WP Session Cookie
                body: JSON.stringify({ username, password })
            });

            const data = await res.json();

            if (data.success) {
                // Clear any lingering data from a previous session or guest
                localStorage.removeItem('hype_cart');
                sessionStorage.clear();

                // Save user and auth nonce to localStorage
                localStorage.setItem('user', JSON.stringify(data.user));
                if (data.nonce) localStorage.setItem('wp_nonce', data.nonce); // Save Nonce for REST headers

                // Redirect to Home (Force reload to clear memory contexts)
                window.location.href = '/';
            } else {
                setError(data.message || 'Login failed');
            }
        } catch (err) {
            setError('Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <Link href="/">
                    <img className="mx-auto h-16 w-auto object-contain cursor-pointer" src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png" alt="Hype" />
                </Link>
                <h2 className="mt-6 text-center text-3xl font-black text-gray-900 uppercase">Sign in to your account</h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    Don't have an account? <Link href="/register" className="font-bold text-purple-600 hover:text-purple-500 hover:underline">Apply for wholesale here</Link>
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form className="space-y-6" onSubmit={handleLogin}>

                        {error && (
                            <div className="bg-red-50 text-red-600 p-3 rounded text-sm font-bold text-center">
                                {error.replace(/<[^>]*>?/gm, '')}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-bold text-gray-700">Username or Email</label>
                            <div className="mt-1">
                                <input
                                    type="text"
                                    required
                                    className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                    value={username}
                                    onChange={(e) => setUsername(e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700">Password</label>
                            <div className="mt-1">
                                <input
                                    type="password"
                                    required
                                    className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition disabled:opacity-50"
                            >
                                {loading ? 'Signing in...' : 'Sign in'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
