import Link from 'next/link';
import { Heart } from 'lucide-react';

export default function ProductCard({ p, user }: { p: any, user: any }) {
    const imageSrc = p.image || 'https://placehold.co/400x400/png?text=No+Image';

    return (
        <div className="bg-white border border-gray-100 group flex flex-col h-full hover:shadow-xl transition-all duration-300">
            {/* Image Area - Zero Padding / Professional Edge-to-Edge */}
            <div className="relative w-full h-80 bg-white overflow-hidden border-b border-gray-50 p-0 m-0">
                <img
                    src={imageSrc}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out p-0 m-0"
                    alt={p.name}
                    referrerPolicy="no-referrer"
                    onError={(e) => { (e.target as HTMLImageElement).src = 'https://placehold.co/400x400/png?text=No+Image'; }}
                />

                {/* Slider Dots Indicator (Visual Only) */}
                <div className="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map(i => (
                        <span key={i} className={`w-1.5 h-1.5 rounded-full ${i === 1 ? 'bg-gray-800' : 'bg-gray-300'}`}></span>
                    ))}
                </div>

                {/* Wishlist Icon */}
                <button className="absolute right-3 top-3 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm shadow-md flex items-center justify-center text-gray-400 hover:text-purple-600 transition-colors">
                    <Heart size={14} />
                </button>
            </div>

            {/* Content Area */}
            <div className="p-4 flex-1 flex flex-col uppercase">
                {/* Brand Name */}
                <div className="text-[11px] font-medium text-gray-400 mb-1 tracking-tight">
                    {p.brand || 'Premium Brand'}
                </div>

                {/* Product Title */}
                <h3 className="font-bold text-gray-800 text-sm leading-snug mb-3 line-clamp-2 h-10 group-hover:text-black transition-colors">
                    {p.name}
                </h3>

                {/* Price / Login Logic (PRESERVED) */}
                <div className="mt-auto">
                    {user ? (
                        <div className="flex flex-col items-start">
                            {/* WHOLESALE PRICING LOGIC */}
                            {(user?.role && (user.role.includes('wholesale') || user.role.includes('customer_wholesale')) && p.wholesale_price) ? (
                                <div className="flex flex-col items-start">
                                    <span className="text-[10px] text-purple-600 font-bold uppercase tracking-widest mb-1">Wholesale Price</span>
                                    <span className="text-xl font-black text-purple-700">${Number(p.wholesale_price).toFixed(2)}</span>
                                    {(p.raw_price || p.regular_price) && (
                                        <span className="text-xs text-gray-400 line-through">Retail: ${Number(p.raw_price || p.regular_price).toFixed(2)}</span>
                                    )}
                                </div>
                            ) : (p.raw_price || p.regular_price) ? (
                                <div className="flex items-center gap-2">
                                    <span className="text-xl font-black text-gray-900">${Number(p.raw_price || p.regular_price).toFixed(2)}</span>
                                    {p.regular_price && (p.raw_price || p.regular_price) < p.regular_price && (
                                        <span className="text-xs text-gray-400 line-through font-bold">${Number(p.regular_price).toFixed(2)}</span>
                                    )}
                                </div>
                            ) : (
                                <span className="text-xl font-black text-gray-900" dangerouslySetInnerHTML={{ __html: p.price }}></span>
                            )}
                        </div>
                    ) : (
                        <div className="flex flex-col gap-0.5">
                            <Link href="/login" className="block w-full text-center py-2 bg-gray-50 text-[10px] font-bold tracking-widest text-gray-500 hover:bg-black hover:text-white transition-colors border border-gray-100 rounded">
                                LOGIN TO VIEW PRICE
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
