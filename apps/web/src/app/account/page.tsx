'use client';

import { useState, useEffect, Suspense } from 'react';
import Navbar from '@/components/Navbar';
import { useRouter, useSearchParams } from 'next/navigation';
import { User, Package, MapPin, LogOut, CreditCard, RefreshCcw } from 'lucide-react';

function AccountContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const activeTab = searchParams ? searchParams.get('tab') || 'dashboard' : 'dashboard';

    const [user, setUser] = useState<any>(null);
    const [orders, setOrders] = useState<any[]>([]);
    const [refunds, setRefunds] = useState<any[]>([]);
    const [addresses, setAddresses] = useState<any>(null);
    const [stats, setStats] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [apiError, setApiError] = useState<string | null>(null);

    // Modal States
    const [isAddressModalOpen, setIsAddressModalOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<any>(null); // For View Order details

    const [formData, setFormData] = useState({
        first_name: '', last_name: '', company: '', address_1: '', city: '', state: '', postcode: '', country: 'US', phone: ''
    });

    const resetForm = () => {
        setFormData({ first_name: '', last_name: '', company: '', address_1: '', city: '', state: '', postcode: '', country: 'US', phone: '' });
    };

    // Fetch Data
    const fetchAccountData = async (userId: number) => {
        try {
            setLoading(true);
            setApiError(null);
            console.log("DEBUG: Calling API for User ID:", userId);

            // 1. Orders
            try {
                const resOrders = await fetch(`/api/wp/wcs/v1/orders?user_id=${userId}`);
                if (resOrders.ok) {
                    const dataOrders = await resOrders.json();
                    console.log("Orders Response:", dataOrders);
                    if (dataOrders.orders) setOrders(dataOrders.orders);
                } else {
                    console.error("Orders API Fail:", resOrders.status);
                }
            } catch (err) { console.error("Orders Fetch Err:", err); }

            // 2. Refunds
            try {
                const resRefunds = await fetch(`/api/wp/wcs/v1/refunds?user_id=${userId}`);
                if (resRefunds.ok) {
                    const dataRefunds = await resRefunds.json();
                    console.log("Refunds Response:", dataRefunds);
                    if (dataRefunds.refunds) setRefunds(dataRefunds.refunds);
                } else {
                    console.error("Refunds API Fail:", resRefunds.status);
                }
            } catch (err) { console.error("Refunds Fetch Err:", err); }

            // 3. Details (Critical for Addresses & Stats)
            try {
                const resDetails = await fetch(`/api/wp/wcs/v1/user-details?user_id=${userId}`);
                if (resDetails.ok) {
                    const dataDetails = await resDetails.json();
                    console.log("DEBUG: Received User Details:", dataDetails);
                    if (dataDetails.billing) {
                        setAddresses(dataDetails.billing);
                        setFormData(dataDetails.billing); // Pre-fill form
                    }
                    if (dataDetails.dashboard_stats) setStats(dataDetails.dashboard_stats);
                } else {
                    console.error("Details API Fail:", resDetails.status);
                    setApiError(`Details API Fail: ${resDetails.status}`);
                }
            } catch (err) {
                console.error("Details Fetch Err:", err);
                setApiError("Critical Data Sync Error");
            }

        } catch (error: any) {
            console.error("ACCOUNT PAGE GLOBAL ERROR:", error);
            setApiError(error.message);
        }
        finally { setLoading(false); }
    };

    useEffect(() => {
        const stored = localStorage.getItem('user');
        if (!stored) { router.push('/login'); return; }
        const userData = JSON.parse(stored);
        setUser(userData);
        fetchAccountData(userData.id);
    }, [router]);

    const handleLogout = () => {
        // Clear all session/cart data
        localStorage.removeItem('user');
        localStorage.removeItem('wp_nonce');
        localStorage.removeItem('hype_cart'); // Proper CartContext Key
        sessionStorage.clear();

        // Force full refresh to clear Context/Memory states (Next.js keeps state on router.push)
        window.location.href = '/';
    };

    const handleSaveAddress = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/wp/wcs/v1/update-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: user.id,
                    billing: formData
                })
            });
            const data = await res.json();
            if (data.success) {
                setAddresses(formData);
                setIsAddressModalOpen(false);
                alert('Address updated successfully!');
            } else {
                alert('Failed to update address');
            }
        } catch (err) { console.error(err); }
    };

    if (!user) return null;

    const renderContent = () => {
        switch (activeTab) {
            case 'orders':
                return (
                    <div>
                        <h2 className="text-2xl font-black text-gray-900 mb-6">Recent Orders</h2>
                        {loading ? <p>Loading...</p> : orders.length > 0 ? (
                            <div className="space-y-4">
                                {orders.map((o) => (
                                    <div key={o.id} className="bg-white border border-gray-100 p-6 rounded-2xl flex items-center justify-between shadow-sm hover:shadow-md transition">
                                        <div>
                                            <div className="flex items-center gap-3 mb-1">
                                                <span className="font-bold text-gray-900">{o.id}</span>
                                                <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${o.status === 'Completed' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600'}`}>
                                                    {o.status}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-500">{o.date} • {o.items_count} Items</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-black text-gray-900" dangerouslySetInnerHTML={{ __html: o.total }}></p>
                                            <button
                                                onClick={() => setSelectedOrder(o)}
                                                className="text-xs font-bold text-purple-600 mt-1 hover:underline block w-full text-right"
                                            >
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-20 bg-gray-50 rounded-2xl border-dashed border-2 border-gray-200">
                                <Package className="mx-auto text-gray-300 w-12 h-12 mb-4" />
                                <h3 className="font-bold text-gray-900">No orders yet</h3>
                                <button onClick={() => router.push('/')} className="mt-4 text-purple-600 font-bold text-sm">Start Shopping</button>
                            </div>
                        )}

                        {/* ORDER DETAILS MODAL */}
                        {selectedOrder && (
                            <div className="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
                                <div className="bg-white rounded-2xl w-full max-w-lg p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                                    <button onClick={() => setSelectedOrder(null)} className="absolute top-4 right-4 text-gray-400 hover:text-black font-bold">✕</button>

                                    <h3 className="text-xl font-black text-gray-900 mb-2">Order {selectedOrder.id}</h3>
                                    <p className="text-sm text-gray-500 mb-6">{selectedOrder.date} • {selectedOrder.status}</p>

                                    <div className="space-y-4 mb-6">
                                        {selectedOrder.line_items?.map((item: any, i: number) => (
                                            <div key={i} className="flex gap-4 items-center border-b border-gray-50 pb-4 last:border-0">
                                                <div className="w-12 h-12 bg-gray-50 rounded-lg flex-shrink-0 flex items-center justify-center">
                                                    {item.image ? <img src={item.image} className="w-full h-full object-contain" /> : <Package size={20} className="text-gray-300" />}
                                                </div>
                                                <div className="flex-1">
                                                    <p className="font-bold text-sm text-gray-900 line-clamp-1">{item.name}</p>
                                                    <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                                </div>
                                                <span className="font-bold text-sm" dangerouslySetInnerHTML={{ __html: item.total }}></span>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="border-t border-gray-100 pt-4 flex justify-between items-center bg-gray-50 -mx-8 -mb-8 p-8 mt-4 rounded-b-2xl">
                                        <span className="font-bold text-gray-500 uppercase text-xs">Total</span>
                                        <span className="font-black text-xl text-gray-900" dangerouslySetInnerHTML={{ __html: selectedOrder.total }}></span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                );
            case 'addresses':
                return (
                    <div>
                        <h2 className="text-2xl font-black text-gray-900 mb-6">Addresses</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="border border-purple-200 bg-purple-50 p-6 rounded-2xl relative">
                                <span className="absolute top-4 right-4 text-[10px] font-bold bg-white text-purple-700 px-2 py-1 rounded shadow-sm uppercase">Default Billing</span>
                                {addresses ? (
                                    <>
                                        <h4 className="font-bold text-gray-900 mb-1">{addresses.first_name} {addresses.last_name}</h4>
                                        <p className="text-sm text-gray-600">{addresses.company}</p>
                                        <p className="text-sm text-gray-600 mt-2">
                                            {addresses.address_1}<br />
                                            {addresses.city}, {addresses.state} {addresses.postcode}<br />
                                            {addresses.country}
                                        </p>
                                        <p className="text-sm text-gray-600 mt-2">{addresses.phone}</p>
                                    </>
                                ) : <p className="text-sm text-gray-400 italic">No address found.</p>}
                                <button
                                    onClick={() => { setFormData(addresses || {}); setIsAddressModalOpen(true); }}
                                    className="mt-4 text-xs font-bold text-purple-700 bg-white px-4 py-2 rounded-lg shadow-sm hover:bg-purple-600 hover:text-white transition"
                                >
                                    Edit Billing Address
                                </button>
                            </div>

                            <div
                                onClick={() => { resetForm(); setIsAddressModalOpen(true); }}
                                className="border-2 border-dashed border-gray-200 p-6 rounded-2xl flex flex-col items-center justify-center text-center cursor-pointer hover:border-purple-300 hover:bg-gray-50 transition group"
                            >
                                <div className="w-10 h-10 rounded-full bg-gray-100 group-hover:bg-purple-100 flex items-center justify-center mb-2 text-gray-400 group-hover:text-purple-600 font-bold text-xl transition">+</div>
                                <span className="font-bold text-sm text-gray-500 group-hover:text-purple-600">Add New Address</span>
                            </div>
                        </div>

                        {/* EDIT ADDRESS MODAL */}
                        {isAddressModalOpen && (
                            <div className="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
                                <div className="bg-white rounded-2xl w-full max-w-lg p-8 shadow-2xl relative">
                                    <h3 className="text-xl font-black text-gray-900 mb-6">{formData.address_1 ? 'Edit Address' : 'Add New Address'}</h3>
                                    <form onSubmit={handleSaveAddress} className="grid grid-cols-2 gap-4">
                                        <input placeholder="First Name" className="border p-3 rounded-lg bg-gray-50" value={formData.first_name} onChange={e => setFormData({ ...formData, first_name: e.target.value })} required />
                                        <input placeholder="Last Name" className="border p-3 rounded-lg bg-gray-50" value={formData.last_name} onChange={e => setFormData({ ...formData, last_name: e.target.value })} required />
                                        <input placeholder="Company" className="col-span-2 border p-3 rounded-lg bg-gray-50" value={formData.company} onChange={e => setFormData({ ...formData, company: e.target.value })} />
                                        <input placeholder="Address" className="col-span-2 border p-3 rounded-lg bg-gray-50" value={formData.address_1} onChange={e => setFormData({ ...formData, address_1: e.target.value })} required />
                                        <input placeholder="City" className="border p-3 rounded-lg bg-gray-50" value={formData.city} onChange={e => setFormData({ ...formData, city: e.target.value })} required />
                                        <input placeholder="State" className="border p-3 rounded-lg bg-gray-50" value={formData.state} onChange={e => setFormData({ ...formData, state: e.target.value })} required />
                                        <input placeholder="ZIP Code" className="border p-3 rounded-lg bg-gray-50" value={formData.postcode} onChange={e => setFormData({ ...formData, postcode: e.target.value })} required />
                                        <input placeholder="Phone" className="border p-3 rounded-lg bg-gray-50" value={formData.phone} onChange={e => setFormData({ ...formData, phone: e.target.value })} />

                                        <div className="col-span-2 flex gap-4 mt-4">
                                            <button type="button" onClick={() => setIsAddressModalOpen(false)} className="flex-1 py-3 text-gray-500 font-bold hover:bg-gray-100 rounded-xl">Cancel</button>
                                            <button type="submit" className="flex-1 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700">Save Address</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        )}
                    </div>
                );
            case 'refunds':
                return (
                    <div>
                        <h2 className="text-2xl font-black text-gray-900 mb-6 font-sans">Refund Requests</h2>
                        {refunds.length > 0 ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {refunds.map((r) => (
                                    <div key={r.id} className="bg-white border border-gray-100 p-6 rounded-2xl shadow-sm">
                                        <div className="flex justify-between items-start mb-2">
                                            <span className="font-bold text-gray-900">{r.id}</span>
                                            <span className="bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wide">{r.status}</span>
                                        </div>
                                        <div className="text-sm text-gray-500 mb-4">
                                            <p>Order: <span className="font-bold text-gray-700">{r.order_id}</span></p>
                                            <p>Date: {r.date}</p>
                                        </div>
                                        <div className="flex justify-between items-center border-t border-gray-50 pt-4">
                                            <span className="text-xs text-gray-400">Refund Amount</span>
                                            <span className="font-black text-gray-900 text-lg" dangerouslySetInnerHTML={{ __html: r.amount }}></span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-20 bg-gray-50 rounded-2xl border-dashed border-2 border-gray-200">
                                <RefreshCcw className="mx-auto text-gray-300 w-12 h-12 mb-4" />
                                <h3 className="font-bold text-gray-900">No Refund Requests</h3>
                                <p className="text-sm text-gray-500 mt-1">You have no active refund requests.</p>
                            </div>
                        )}
                    </div>
                );
            default: // Dashboard
                return (
                    <div>
                        <h2 className="text-2xl font-black text-gray-900 mb-2">Hello, {user.name.split(' ')[0]}!</h2>
                        <p className="text-gray-500 mb-8">From your dashboard you can view your <span className="text-purple-600 font-bold cursor-pointer" onClick={() => router.push('/account?tab=orders')}>recent orders</span>, and manage your <span className="text-purple-600 font-bold cursor-pointer" onClick={() => router.push('/account?tab=addresses')}>addresses</span>.</p>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><Package size={24} /></div>
                                <div>
                                    <span className="text-2xl font-black text-gray-900 block leading-none">{stats?.total_orders || 0}</span>
                                    <span className="text-xs font-bold text-gray-400 uppercase">Total Orders</span>
                                </div>
                            </div>
                            <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center"><CreditCard size={24} /></div>
                                <div>
                                    <span className="text-xl font-black text-gray-900 block leading-none">${stats?.total_spent || '0.00'}</span>
                                    <span className="text-xs font-bold text-gray-400 uppercase">Total Spent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                );
        }
    };

    const TABS = [
        { id: 'dashboard', label: 'Dashboard', icon: User },
        { id: 'orders', label: 'Orders', icon: Package },
        { id: 'addresses', label: 'Addresses', icon: MapPin },
        { id: 'refunds', label: 'Refunds', icon: RefreshCcw },
    ];

    return (
        <main className="min-h-screen bg-[#FDFDFD] font-sans">
            <Navbar />
            <div className="container mx-auto px-6 py-12">
                <div className="flex flex-col lg:flex-row gap-12">
                    {/* Sidebar */}
                    <div className="w-full lg:w-72 flex-shrink-0">
                        <div className="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm">
                            <div className="px-4 py-6 text-center border-b border-gray-50 mb-2">
                                <div className="w-20 h-20 bg-purple-100 text-purple-600 rounded-full mx-auto flex items-center justify-center text-2xl font-black mb-3 border-4 border-white shadow-lg">
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <h3 className="font-bold text-gray-900">{user.name}</h3>
                                <p className="text-xs text-gray-500">{user.email}</p>
                            </div>
                            <nav className="space-y-1">
                                {TABS.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => router.push(`/account?tab=${tab.id}`)}
                                        className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all ${activeTab === tab.id
                                            ? 'bg-purple-600 text-white shadow-lg shadow-purple-200'
                                            : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                    >
                                        <tab.icon size={18} />
                                        {tab.label}
                                    </button>
                                ))}
                                <button
                                    onClick={handleLogout}
                                    className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 transition-all mt-4"
                                >
                                    <LogOut size={18} />
                                    Sign Out
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* Main Content */}
                    <div className="flex-1">
                        {apiError && (
                            <div className="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-2xl text-sm font-bold">
                                ⚠️ API ERROR: {apiError}. Check console for details. (User ID: {user?.id})
                            </div>
                        )}
                        {renderContent()}
                    </div>
                </div>
            </div>
        </main>
    );
}

export default function AccountPage() {
    return (
        <Suspense fallback={
            <div className="min-h-screen flex items-center justify-center bg-[#FDFDFD]">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-600"></div>
            </div>
        }>
            <AccountContent />
        </Suspense>
    );
}
