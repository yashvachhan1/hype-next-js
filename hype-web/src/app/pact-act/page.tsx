'use client';

import Navbar from '@/components/Navbar';
import Link from 'next/link';
import { ShieldCheck, FileText, CheckCircle, Info, UploadCloud } from 'lucide-react';
import { useState } from 'react';

export default function PactActPage() {
    const [submitting, setSubmitting] = useState(false);
    const [formSuccess, setFormSuccess] = useState('');
    const [formError, setFormError] = useState('');
    const [files, setFiles] = useState<Record<string, string>>({});

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const fileName = e.target.files[0].name;
            const fieldName = e.target.name;
            setFiles(prev => ({ ...prev, [fieldName]: fileName }));
        }
    };

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setSubmitting(true);
        setFormError('');
        setFormSuccess('');

        const formData = new FormData(e.currentTarget);

        // Append Form ID
        formData.append('form_id', '2915');

        try {
            const res = await fetch('/api/wp/wcs/v1/form/submit', {
                method: 'POST',
                // Content-Type header must be undefined so browser sets generic multipart boundary
                body: formData
            });

            const result = await res.json();

            if (res.ok && result.success) {
                setFormSuccess(result.entry_id || 'Submitted Successfully');
                window.scrollTo(0, 0);
            } else {
                setFormError(result.message || 'Submission failed. Please try again.');
            }
        } catch (err) {
            setFormError('Network error. Check your connection.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <main className="min-h-screen bg-gray-50 flex flex-col font-sans">
            <Navbar />

            {/* Header Section */}
            <div className="bg-[#2d004b] text-white py-16">
                <div className="container mx-auto px-6 text-center">
                    <div className="inline-flex items-center justify-center p-3 bg-white/10 rounded-full mb-6 backdrop-blur-sm">
                        <ShieldCheck className="w-8 h-8 text-purple-300" />
                    </div>
                    <h1 className="text-4xl md:text-5xl font-black uppercase tracking-tight mb-4">
                        PACT ACT Compliance
                    </h1>
                    <p className="text-purple-200 max-w-2xl mx-auto text-lg leading-relaxed">
                        To comply with the PACT Act, please provide the following details.
                    </p>
                </div>
            </div>

            <div className="container mx-auto px-6 py-12 pb-24 -mt-10">
                <div className="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div className="p-1 text-center bg-gray-50 border-b border-gray-100">
                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] py-2 block">Official PACT ACT Registration Form</span>
                    </div>

                    <div className="p-8 md:p-12">
                        {formSuccess ? (
                            <div className="text-center py-20">
                                <div className="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                                    <CheckCircle size={40} />
                                </div>
                                <h2 className="text-3xl font-black text-gray-900 mb-4">Submission Received!</h2>
                                <p className="text-gray-500 max-w-md mx-auto mb-8">Your documents have been securely uploaded. Our compliance team will review them shortly.</p>
                                <button onClick={() => window.location.reload()} className="bg-purple-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition">Submit Another Form</button>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-10 max-w-5xl mx-auto">
                                {formError && <div className="bg-red-50 text-red-600 p-4 rounded-xl border border-red-100 font-bold text-center">{formError}</div>}

                                {/* Section 1: Business Owner Information */}
                                <section>
                                    <h3 className="text-lg font-bold text-gray-900 mb-6 border-b pb-2">Business Owner Information</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">First Name</label>
                                            <input name="name-1" placeholder="" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Last Name</label>
                                            <input name="name-2" placeholder="" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2 md:col-span-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Company Name</label>
                                            <input name="text-1" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                    </div>
                                </section>

                                {/* Section 2: Main Company Address */}
                                <section>
                                    <h3 className="text-lg font-bold text-gray-900 mb-6 border-b pb-2">Main Company Address</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="md:col-span-2 space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Address</label>
                                            <input name="address-1-street_address" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="md:col-span-2 space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Address Line 2</label>
                                            <input name="address-1-address_line_2" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">City</label>
                                            <input name="address-1-city" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">State / Province / Region</label>
                                            <input name="address-1-state" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Zip / Postal Code</label>
                                            <input name="address-1-zip" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Country</label>
                                            <input name="address-1-country" defaultValue="United States" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                    </div>
                                </section>

                                {/* Section 3: Contact Information */}
                                <section>
                                    <h3 className="text-lg font-bold text-gray-900 mb-6 border-b pb-2">Contact Information</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Phone</label>
                                            <input name="phone-1" placeholder="(555) 555-5555" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-gray-500 uppercase">Email <span className="text-red-500">*</span></label>
                                            <input required name="email-1" type="email" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                        </div>
                                    </div>
                                </section>

                                {/* Section 4: Federal PIN */}
                                <section>
                                    <div className="space-y-2">
                                        <label className="text-lg font-bold text-gray-900 border-b pb-2 block mb-6">Federal PIN Number</label>
                                        <input name="text-2" className="w-full h-12 bg-gray-50 border border-gray-200 rounded-lg px-4 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition" />
                                    </div>
                                </section>

                                {/* Section 5: File Uploads */}
                                <section className="space-y-8">
                                    <div className="border-t border-gray-100 pt-8">
                                        <p className="text-sm text-gray-500 mb-6">Please upload scanned documents only. Photos from a cellphone will not be accepted.</p>
                                    </div>

                                    {[
                                        { label: 'Business License', name: 'upload-1' },
                                        { label: 'Sales Tax License / Resellers Certificate', name: 'upload-2' },
                                        { label: 'OTP / Tobacco License', name: 'upload-3' },
                                        { label: 'Federal EIN Document (FEIN)', name: 'upload-4' },
                                        { label: 'Certificate of Good Standing', name: 'upload-5' },
                                        { label: 'Upload Identity Document', name: 'upload-6' }
                                    ].map((field) => (
                                        <div key={field.name} className="space-y-2">
                                            <label className="text-sm font-bold text-gray-700">{field.label}</label>
                                            <div className="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:bg-purple-50/50 hover:border-purple-200 transition group cursor-pointer relative">
                                                <input
                                                    type="file"
                                                    name={field.name}
                                                    onChange={handleFileChange}
                                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                                />
                                                <div className="flex flex-col items-center justify-center gap-3">
                                                    {files[field.name] ? (
                                                        <>
                                                            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                                                <FileText className="text-green-600" size={24} />
                                                            </div>
                                                            <p className="text-green-700 font-bold bg-green-50 px-3 py-1 rounded-full text-sm">{files[field.name]}</p>
                                                            <p className="text-xs text-gray-400">Click to change file</p>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center group-hover:scale-110 transition duration-300">
                                                                <UploadCloud className="text-gray-400 group-hover:text-purple-600" size={24} />
                                                            </div>
                                                            <p className="text-gray-500 text-sm font-medium">Drop your file here or <span className="text-purple-600 underline">click here to upload</span></p>
                                                            <p className="text-xs text-gray-400">You can upload up to 1 files.</p>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </section>

                                <div className="pt-8">
                                    <button
                                        type="submit"
                                        disabled={submitting}
                                        className="w-full py-5 bg-purple-600 text-white rounded-xl font-black uppercase tracking-[0.2em] hover:bg-black transition shadow-xl disabled:opacity-70 flex items-center justify-center gap-3 text-sm md:text-base relative overflow-hidden group"
                                    >
                                        <span className="relative z-10">{submitting ? 'Directing to Compliance...' : 'Submit PACT ACT Form'}</span>
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>

            {/* Footer */}
            <footer className="mt-auto bg-black text-white pt-20 pb-10">
                <div className="container mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                    <div className="col-span-1 md:col-span-2">
                        <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png" className="h-10 mb-6 bg-white p-2 rounded-lg" />
                        <p className="text-gray-400 max-w-sm leading-relaxed">The premier wholesale distributor for vape shop owners. We provide the latest products at the best prices with shipping you can count on.</p>
                    </div>
                    <div>
                        <h4 className="font-bold text-lg mb-6 text-white">Quick Links</h4>
                        <ul className="space-y-4 text-gray-400 text-sm">
                            <li><Link href="/account" className="hover:text-purple-400 transition">My Account</Link></li>
                            <li><Link href="/pact-act" className="hover:text-purple-400 transition font-bold text-purple-400 underline">PACT ACT Compliance</Link></li>
                            <li><a href="#" className="hover:text-purple-400 transition">Shipping Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div className="container mx-auto px-6 border-t border-white/10 pt-8 flex justify-between text-xs text-gray-500">
                    <p>&copy; 2026 Hype Distribution. Wholesale Only.</p>
                </div>
            </footer>
        </main>
    );
}
