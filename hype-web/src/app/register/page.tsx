'use client';

import { useState } from 'react';
import Link from 'next/link';
import Navbar from '@/components/Navbar';
import { ChevronRight, UploadCloud, UserPlus, FileText, Briefcase } from 'lucide-react';
import { useRouter } from 'next/navigation';

export default function RegisterPage() {
    const router = useRouter();
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        company_name: '',
        tax_id: '',
        address_1: '',
        city: '',
        state: '',
        postcode: '',
        password: '',
        confirm_password: ''
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        if (formData.password !== formData.confirm_password) {
            setError('Passwords do not match');
            return;
        }

        setLoading(true);

        try {
            // Use Proxy Route
            const res = await fetch('/api/wp/wcs/v1/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Registration failed');
            }

            setSuccess(true);

        } catch (err: any) {
            setError(err.message);
            window.scrollTo(0, 0);
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <div className="min-h-screen bg-[#FDFDFD]">
                <Navbar />
                <div className="container mx-auto px-6 py-20 flex flex-col items-center text-center">
                    <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center text-green-500 mb-6 shadow-sm">
                        <UserPlus size={40} />
                    </div>
                    <h1 className="text-4xl font-black text-gray-900 mb-4">Application Received!</h1>
                    <p className="text-gray-500 mb-8 max-w-lg text-lg">
                        Thank you for registering with <strong>Hype Distribution</strong>. Your wholesale account is currently <strong>Pending Approval</strong>.
                        <br /><br />
                        Our team will verify your business details (Tax ID/EIN) and notify you via email once your account is active (usually within 24 hours).
                    </p>
                    <Link href="/" className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-10 rounded-xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-transform active:scale-95">
                        Return to Home
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <main className="min-h-screen bg-[#FDFDFD] pb-20 font-sans">
            <Navbar />

            <div className="bg-gray-50 border-b border-gray-100 mb-12">
                <div className="container mx-auto px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                    <Link href="/" className="hover:text-purple-600">Home</Link>
                    <ChevronRight size={12} />
                    <span className="text-gray-900">Wholesale Registration</span>
                </div>
            </div>

            <div className="container mx-auto px-4 max-w-4xl">
                <div className="text-center mb-12">
                    <h1 className="text-4xl font-black text-gray-900 mb-4">Wholesale Application</h1>
                    <p className="text-gray-500 max-w-xl mx-auto">Create a wholesale account to access exclusive bulk pricing. Please ensure all business details (Tax ID) are accurate for faster approval.</p>
                </div>

                {error && (
                    <div className="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-r shadow-sm">
                        <p className="font-bold">Registration Error</p>
                        <p className="text-sm">{error}</p>
                    </div>
                )}

                <div className="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <form onSubmit={handleSubmit} className="p-8 lg:p-12 space-y-8">

                        {/* Section 1: Personal Info */}
                        <div className="space-y-6">
                            <div className="flex items-center gap-3 mb-6 pb-2 border-b border-gray-100">
                                <UserPlus className="text-purple-600" size={24} />
                                <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight">Contact Information</h2>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">First Name *</label>
                                    <input required name="first_name" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="John" />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Last Name *</label>
                                    <input required name="last_name" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="Doe" />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address *</label>
                                    <input required type="email" name="email" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="john@company.com" />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Phone Number *</label>
                                    <input required type="tel" name="phone" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="(555) 123-4567" />
                                </div>
                            </div>
                        </div>

                        {/* Section 2: Business Info */}
                        <div className="space-y-6 pt-6">
                            <div className="flex items-center gap-3 mb-6 pb-2 border-b border-gray-100">
                                <Briefcase className="text-purple-600" size={24} />
                                <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight">Business Details</h2>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Company / Shop Name *</label>
                                    <input required name="company_name" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="Vape Shop LLC" />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tax ID / EIN *</label>
                                    <input required name="tax_id" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="XX-XXXXXXX" />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Street Address *</label>
                                <input required name="address_1" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="123 Business Rd" />
                            </div>

                            <div className="grid grid-cols-3 gap-6">
                                <div className="col-span-1">
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">City *</label>
                                    <input required name="city" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="City" />
                                </div>
                                <div className="col-span-1">
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">State *</label>
                                    <input required name="state" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="State" />
                                </div>
                                <div className="col-span-1">
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Zip *</label>
                                    <input required name="postcode" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="Zip" />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Business License / Tax Doc (Optional)</label>
                                <div className="border-2 border-dashed border-gray-200 rounded-xl p-8 hover:bg-gray-50 transition cursor-pointer flex flex-col items-center justify-center text-gray-400">
                                    <UploadCloud size={32} className="mb-2" />
                                    <span className="text-xs font-bold uppercase">Click to upload image</span>
                                </div>
                                <p className="text-[10px] text-gray-400 mt-2">* You can email this later to approval@hypedistribution.com</p>
                            </div>
                        </div>

                        {/* Section 3: Password */}
                        <div className="space-y-6 pt-6">
                            <div className="flex items-center gap-3 mb-6 pb-2 border-b border-gray-100">
                                <FileText className="text-purple-600" size={24} />
                                <h2 className="text-xl font-black text-gray-900 uppercase tracking-tight">Security</h2>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Password *</label>
                                    <input required type="password" name="password" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="••••••••" />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Confirm Password *</label>
                                    <input required type="password" name="confirm_password" onChange={handleChange} className="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-gray-900 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none transition font-medium" placeholder="••••••••" />
                                </div>
                            </div>
                        </div>

                        <div className="pt-8">
                            <button
                                type="submit"
                                disabled={loading}
                                className={`w-full bg-purple-600 text-white py-4 rounded-xl font-black uppercase tracking-wider text-lg shadow-lg shadow-purple-200 hover:bg-purple-700 transition-transform active:scale-95 flex items-center justify-center gap-2 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
                            >
                                {loading ? (
                                    <>Processing Application...</>
                                ) : (
                                    <>Submit Application <ChevronRight size={18} /></>
                                )}
                            </button>
                            <p className="text-center text-xs text-gray-400 mt-4">By clicking submit, you agree to our Terms of Service and Privacy Policy.</p>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    );
}
