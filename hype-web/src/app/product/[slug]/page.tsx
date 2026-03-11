'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { ShoppingCart, Heart, Check, ChevronRight, User } from 'lucide-react';
import Navbar from '@/components/Navbar';
import ProductCard from '@/components/ProductCard';
import { useCart } from '@/context/CartContext';
import AddToCartModal from '@/components/AddToCartModal';

export default function ProductPage() {
    const params = useParams();
    const slug = params?.slug as string;

    const [product, setProduct] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [user, setUser] = useState<any>(null);
    const [mainImage, setMainImage] = useState('');

    // Simple Product State
    const [quantity, setQuantity] = useState(1);

    // Variable Product State
    const [variationsMap, setVariationsMap] = useState<{ [key: number]: number }>({});

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [addedItems, setAddedItems] = useState<any[]>([]);
    const { addToCart } = useCart();

    // Fetch User
    useEffect(() => {
        const stored = localStorage.getItem('user');
        if (stored) setUser(JSON.parse(stored));
    }, []);

    // ... (keep fetchProduct and other effects) ...

    const handleAddToCart = () => {
        let itemsToAdd: any[] = [];

        if (product.type === 'variable') {
            // Collect selected variations
            product.variations.forEach((v: any) => {
                const qty = variationsMap[v.id] || 0;
                if (qty > 0) {
                    itemsToAdd.push({
                        id: v.id,
                        product_id: product.id,
                        name: product.name,
                        price: Number(v.price),
                        quantity: qty,
                        image: v.image || product.image,
                        sku: v.sku,
                        variation_data: v.display_name // e.g. "Color: Red"
                    });
                }
            });
        } else {
            // Simple Product
            itemsToAdd.push({
                id: product.id,
                product_id: product.id,
                name: product.name,
                price: Number(product.raw_price || product.regular_price || 0),
                quantity: quantity,
                image: product.image,
                sku: product.sku
            });
        }

        if (itemsToAdd.length > 0) {
            addToCart(itemsToAdd);
            setAddedItems(itemsToAdd);
            setIsModalOpen(true);

            // Reset state if needed, or keep for user convenience
            // setVariationsMap({});
        }
    };

    // Fetch Product
    useEffect(() => {
        async function fetchProduct() {
            if (!slug) return;
            setLoading(true);
            try {
                const res = await fetch(`/api/wp/wcs/v1/products?slug=${slug}`);
                const data = await res.json();

                if (data.products && data.products.length > 0) {
                    const p = data.products[0];
                    setProduct(p);
                    setMainImage(p.image);
                }
            } catch (err) {
                console.error(err);
            } finally {
                setLoading(false);
            }
        }
        fetchProduct();
    }, [slug]);

    // Derived Stats for Bulk Order
    const totalItems = Object.values(variationsMap).reduce((a, b) => a + b, 0);
    const totalPrice = product?.variations ? product.variations.reduce((acc: number, v: any) => {
        const qty = variationsMap[v.id] || 0;
        return acc + (Number(v.price) * qty);
    }, 0) : 0;

    const handleVariationQtyChange = (id: number, val: string) => {
        const qty = parseInt(val) || 0;
        setVariationsMap(prev => ({ ...prev, [id]: qty }));
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-white">
                <Navbar />
                <div className="container mx-auto px-6 py-20 flex justify-center items-center h-[60vh]">
                    <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-purple-600"></div>
                </div>
            </div>
        );
    }

    if (!product) {
        return (
            <div className="min-h-screen bg-white">
                <Navbar />
                <div className="container mx-auto px-6 py-20 text-center">
                    <h1 className="text-2xl font-bold text-gray-900">Product Not Found</h1>
                    <Link href="/" className="text-purple-600 underline mt-4 inline-block">Return Home</Link>
                </div>
            </div>
        );
    }

    return (
        <main className="min-h-screen bg-[#FDFDFD] font-sans text-gray-800 pb-20">
            <Navbar />

            {/* Breadcrumb */}
            <div className="bg-gray-50 border-b border-gray-100 mb-8">
                <div className="container mx-auto px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                    <Link href="/" className="hover:text-purple-600">Home</Link>
                    <ChevronRight size={12} />
                    <Link href="/shop" className="hover:text-purple-600">Shop</Link>
                    <ChevronRight size={12} />
                    <span className="text-gray-900">{product.name}</span>
                </div>
            </div>

            <div className="container mx-auto px-6 flex flex-col lg:flex-row gap-16">

                {/* --- Left: Image Gallery --- */}
                <div className="w-full lg:w-1/2">
                    <div className="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm mb-6 relative group overflow-hidden">
                        {product.badge && (
                            <span className="absolute top-6 left-6 bg-red-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest z-10 shadow-lg shadow-red-200">
                                {product.badge}
                            </span>
                        )}
                        <img
                            src={mainImage || 'https://placehold.co/600x600?text=No+Image'}
                            alt={product.name}
                            className="w-full h-[400px] lg:h-[500px] object-contain group-hover:scale-105 transition duration-500"
                        />
                    </div>

                    {/* Thumbnails */}
                    {product.gallery && product.gallery.length > 0 && (
                        <div className="flex gap-4 overflow-x-auto pb-2 no-scrollbar">
                            {product.gallery.map((img: string, idx: number) => (
                                <button
                                    key={idx}
                                    onClick={() => setMainImage(img)}
                                    className={`w-20 h-20 flex-shrink-0 bg-white border rounded-xl p-2 transition-all ${mainImage === img ? 'border-purple-600 shadow-md ring-2 ring-purple-100' : 'border-gray-200 hover:border-gray-300'}`}
                                >
                                    <img src={img} className="w-full h-full object-contain" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* --- Right: Product Info --- */}
                <div className="w-full lg:w-1/2 flex flex-col">
                    <h1 className="text-2xl md:text-3xl font-black text-gray-900 mb-2 leading-tight uppercase tracking-tight">{product.name}</h1>
                    <div className="text-xs font-bold text-gray-400 mb-6 uppercase tracking-wider">Brand: <span className="text-purple-600">Hype Distribution</span></div>

                    {/* B2B / Variable Product Layout */}
                    {product.type === 'variable' && product.variations && product.variations.length > 0 ? (
                        <div className="mb-8">
                            <div className="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <div className="grid grid-cols-12 bg-gray-50 border-b border-gray-200 py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                    <div className="col-span-6">Variation</div>
                                    <div className="col-span-3 text-center">Stock</div>
                                    <div className="col-span-3 text-right">Qty</div>
                                </div>
                                <div className="max-h-[350px] overflow-y-auto no-scrollbar">
                                    {product.variations.map((v: any) => (
                                        <div key={v.id} className="grid grid-cols-12 items-center py-3 px-4 border-b border-gray-100 last:border-0 hover:bg-purple-50/20 transition group">
                                            {/* Name & Price */}
                                            <div className="col-span-6 flex flex-col">
                                                <span className="font-bold text-gray-800 text-sm">{v.display_name}</span>
                                                {user ? (
                                                    <span className="text-[10px] font-bold text-purple-600 mt-0.5">${Number(v.price).toFixed(2)}</span>
                                                ) : (
                                                    <span className="text-[10px] text-red-300 italic mt-0.5">Login for price</span>
                                                )}
                                            </div>

                                            {/* Stock Status */}
                                            <div className="col-span-3 text-center">
                                                <span className={`text-[9px] font-black uppercase px-2 py-0.5 rounded-full tracking-wider ${v.stock_status === 'instock' ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-red-50 text-red-500 border border-red-100'}`}>
                                                    {v.stock_status === 'instock' ? 'In Stock' : 'Out'}
                                                </span>
                                            </div>

                                            {/* Qty Input */}
                                            <div className="col-span-3 flex justify-end">
                                                {v.stock_status === 'instock' && user ? (
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        placeholder="0"
                                                        className="w-14 h-9 border border-gray-200 rounded-lg text-center font-bold text-sm text-gray-900 outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition bg-gray-50 focus:bg-white"
                                                        onChange={(e) => handleVariationQtyChange(v.id, e.target.value)}
                                                    />
                                                ) : (
                                                    <div className="w-14 h-9 bg-gray-50 rounded-lg border border-transparent opacity-50 cursor-not-allowed"></div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="bg-gray-100 border-t border-gray-200 p-4 flex items-center justify-between">
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Items</span>
                                        <span className="text-xl font-black text-gray-900">{totalItems}</span>
                                    </div>
                                    <div className="hidden sm:flex flex-col text-right mr-4">
                                        <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Price</span>
                                        <span className="text-xl font-black text-purple-600">${totalPrice.toFixed(2)}</span>
                                    </div>
                                    <button
                                        onClick={handleAddToCart}
                                        className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-purple-200 uppercase tracking-wider text-[10px] transition-transform active:scale-95 flex items-center gap-2">
                                        <ShoppingCart size={16} />
                                        Add to cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        // SIMPLE PRODUCT DYNAMIC
                        <>
                            {/* Meta & Stock */}
                            <div className="flex items-center gap-4 mb-6 text-sm font-bold">
                                <span className={`flex items-center gap-1 ${product.stock_status === 'instock' ? 'text-green-600' : 'text-red-500'}`}>
                                    {product.stock_status === 'instock' ? <><Check size={16} /> In Stock</> : 'Out of Stock'}
                                </span>
                                {product.sku && <span className="text-gray-400">SKU: <span className="text-gray-600">{product.sku}</span></span>}
                            </div>

                            {/* Price Block */}
                            <div className="mb-8 p-6 bg-purple-50 rounded-2xl border border-purple-100">
                                {user ? (
                                    <div className="flex flex-col">
                                        {(product.raw_price || product.regular_price) ? (
                                            <div className="flex items-baseline gap-3">
                                                <span className="text-3xl font-black text-purple-700">${Number(product.raw_price || product.regular_price).toFixed(2)}</span>
                                                {product.regular_price && (product.raw_price || product.regular_price) < product.regular_price && (
                                                    <span className="text-lg text-gray-400 line-through font-bold">${Number(product.regular_price).toFixed(2)}</span>
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-3xl font-black text-purple-700" dangerouslySetInnerHTML={{ __html: product.price_html }}></span>
                                        )}
                                        <span className="text-xs text-purple-400 font-bold uppercase tracking-wider mt-1">Wholesale Pricing Applied</span>
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-3">
                                        <div className="p-3 bg-white rounded-full text-purple-600">
                                            <User size={24} />
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="font-bold text-gray-900">Wholesale Price Hidden</span>
                                            <Link href="/login" className="text-sm font-medium text-purple-600 hover:underline">Login to unlock pricing</Link>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Actions (Simple) */}
                            {user && (
                                <div className="flex flex-col sm:flex-row gap-4 mb-8">
                                    <div className="flex items-center border border-gray-300 rounded-xl h-14 w-32 bg-white">
                                        <button
                                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                            className="w-10 h-full flex items-center justify-center text-gray-500 hover:text-purple-600 font-bold text-lg"
                                        >-</button>
                                        <input
                                            type="text"
                                            value={quantity}
                                            readOnly
                                            className="flex-1 w-full text-center font-bold text-gray-900 outline-none"
                                        />
                                        <button
                                            onClick={() => setQuantity(quantity + 1)}
                                            className="w-10 h-full flex items-center justify-center text-gray-500 hover:text-purple-600 font-bold text-lg"
                                        >+</button>
                                    </div>

                                    <button
                                        onClick={handleAddToCart}
                                        className="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold h-14 rounded-xl flex items-center justify-center gap-3 shadow-xl shadow-purple-200 transition-transform active:scale-95 uppercase tracking-wide">
                                        <ShoppingCart size={20} />
                                        Add to Order
                                    </button>

                                    {/* Wishlist */}
                                    <button className="h-14 w-14 border border-gray-200 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 hover:border-red-100 transition">
                                        <Heart size={24} />
                                    </button>
                                </div>
                            )}

                            {/* Wishlist Mobile */}
                            {user && (
                                <div className="sm:hidden mb-8">
                                    <button className="w-full h-12 border border-gray-200 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-500 transition gap-2 text-sm font-bold">
                                        <Heart size={20} /> Add to Wishlist
                                    </button>
                                </div>
                            )}
                        </>
                    )}

                </div>
            </div>

            {/* Full Width Description Section */}
            <div className="container mx-auto px-6 mt-16">
                <div className="bg-white rounded-3xl p-8 md:p-12 border border-gray-100 shadow-sm">
                    <div className="flex items-center gap-4 mb-8 border-b border-gray-100 pb-4">
                        <span className="text-sm font-black bg-[#aa00ff] text-white px-4 py-2 rounded-full uppercase tracking-wider shadow-lg shadow-purple-200">Description</span>
                    </div>
                    <div
                        className="prose prose-lg prose-purple text-gray-600 leading-relaxed font-medium max-w-none"
                        dangerouslySetInnerHTML={{ __html: product.description || '<p>No description available.</p>' }}
                    />
                </div>
            </div>

            {/* Related Products */}
            {product.related_products && product.related_products.length > 0 && (
                <div className="container mx-auto px-6 mt-16 mb-12">
                    <div className="flex items-center gap-4 mb-8">
                        <h2 className="text-2xl font-black text-gray-900 uppercase tracking-tight">Related Products</h2>
                        <div className="flex-1 h-px bg-gray-200"></div>
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {product.related_products.map((rp: any) => (
                            <ProductCard key={rp.id} p={rp} user={user} />
                        ))}
                    </div>
                </div>
            )}
            {/* Modal */}
            <AddToCartModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                items={addedItems}
            />
        </main>
    )
}
