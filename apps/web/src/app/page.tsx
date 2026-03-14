'use client';

import { Search, ShoppingCart, User, Menu, Star, Truck, ShieldCheck, Zap, ArrowRight, Package } from 'lucide-react';
import Link from 'next/link';
import { useState, useEffect } from 'react';
import Navbar from '@/components/Navbar';
import WpProductCard from '@/components/WpProductCard';
import Hero3D from '@/components/Hero3D';

// --- COMPONENTS ---
import { fetchTrendingProducts } from '@/api/trending';
import { fetchNewArrivals } from '@/api/new-arrivals';
import { fetchBestSellers } from '@/api/best-sellers';


// 2. HERO SECTION (REDESIGNED FOR WHOLESALE)
// ... (rest of file)
const HERO_SLIDES = [
  {
    id: 1,
    title: "Geek Bar Pulse 15k",
    image: "https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/1st-5.jpg",
    profit: "50%",
    badge: "Best Seller"
  },
  {
    id: 2,
    title: "Lost Mary MT15000",
    image: "https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/2nd-5.jpg", // Assuming similar naming or fallback
    profit: "45%",
    badge: "New Arrival"
  },
  {
    id: 3,
    title: "Raz TN9000",
    image: "https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/3rd-5.jpg", // Assuming similar naming or fallback
    profit: "60%",
    badge: "High Margin"
  }
];

// --- BANNER SLIDER (YESHA LAYOUT - NO TEXT) ---
function BannerSlider() {
  const banners = [
    { src: "/media/kumi-video.mp4" },
    { src: "/media/flum-video.mp4" },
    { src: "/media/nexa-video.mp4" }
  ];

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Dynamic Dots */}
      <div className="flex gap-1.5 justify-center mb-6">
        <div className="w-8 h-1.5 bg-[#6342ff] rounded-full"></div>
        <div className="w-2.5 h-1.5 bg-gray-300 rounded-full"></div>
        <div className="w-2.5 h-1.5 bg-gray-300 rounded-full"></div>
        <div className="w-2.5 h-1.5 bg-gray-300 rounded-full"></div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {banners.map((b, i) => (
          <div key={i} className="relative aspect-[1.8/1] rounded-2xl overflow-hidden shadow-lg border-2 border-white/50">
            <video
              src={b.src}
              autoPlay
              loop
              muted
              playsInline
              className="w-full h-full object-cover"
            />
          </div>
        ))}
      </div>
    </div>
  );
}

// --- HOT TICKER (OFF-STAMP BANNER IMAGE) ---
function HotTicker() {
  return (
    <div className="w-full">
      <img
        src="/media/off-stamp-banner.webp"
        className="w-full h-auto object-cover"
        alt="Off Stamp Experience"
      />
    </div>
  );
}

function Hero() {
  const [currentSlide, setCurrentSlide] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % HERO_SLIDES.length);
    }, 4000);
    return () => clearInterval(timer);
  }, []);

  return (
    <div className="relative bg-[#050505] text-white pt-12 pb-24 md:pt-20 md:pb-32 overflow-hidden rounded-b-[3rem] shadow-2xl">

      {/* Abstract Background Elements */}
      <div className="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div className="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-purple-600/30 rounded-full blur-[120px] mix-blend-screen animate-pulse"></div>
        <div className="absolute bottom-[-10%] right-[-5%] w-[500px] h-[500px] bg-blue-600/20 rounded-full blur-[100px] mix-blend-screen"></div>
        {/* Subtle Grid Pattern */}
        <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:40px_40px] opacity-20"></div>
      </div>

      <div className="container mx-auto px-6 relative z-10">
        <div className="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">

          {/* Left Content */}
          <div className="flex-1 text-center lg:text-left">
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 backdrop-blur-md mb-8">
              <span className="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
              <span className="text-xs font-bold tracking-widest uppercase text-gray-300">Wholesale Only • B2B Partner</span>
            </div>

            <h1 className="text-3xl md:text-5xl lg:text-7xl font-black leading-[0.95] tracking-tight mb-6">
              FUEL YOUR <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#aa00ff] via-purple-400 to-white">BUSINESS GROWTH</span>
            </h1>

            <p className="text-gray-400 text-sm md:text-lg lg:text-xl font-medium mb-10 max-w-2xl mx-auto lg:mx-0 leading-relaxed px-4 md:px-0">
              America's leading distributor of premium vape products. We supply smoke shops with top-tier brands like <span className="text-white font-bold">Geek Bar</span>, <span className="text-white font-bold">Lost Mary</span>, and <span className="text-white font-bold">Raz</span> at unbeatable wholesale rates.
            </p>

            <div className="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start px-6 md:px-0">
              <Link href="/category/all" className="w-full sm:w-auto px-8 py-3.5 md:py-4 bg-[#aa00ff] hover:bg-[#8e00d6] text-white rounded-xl font-black uppercase tracking-wider shadow-[0_10px_40px_-10px_rgba(170,0,255,0.4)] hover:shadow-[0_20px_40px_-10px_rgba(170,0,255,0.6)] transition-all transform hover:-translate-y-1 text-xs md:text-sm text-center">
                View Catalog
              </Link>
              <Link href="/register" className="w-full sm:w-auto px-8 py-3.5 md:py-4 bg-white/5 border border-white/10 hover:bg-white/10 text-white rounded-xl font-bold uppercase tracking-wider backdrop-blur-sm transition-all flex items-center justify-center gap-2 text-xs md:text-sm">
                <User size={18} />
                Register Account
              </Link>
            </div>

            <div className="mt-12 flex items-center justify-center lg:justify-start gap-8 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
              {/* Mock Trust Logos */}
              <div className="flex items-center gap-2">
                <ShieldCheck className="text-purple-400" />
                <span className="text-xs font-bold uppercase tracking-wide">Authorized Distro</span>
              </div>
              <div className="w-px h-8 bg-white/20"></div>
              <div className="flex items-center gap-2">
                <Truck className="text-purple-400" />
                <span className="text-xs font-bold uppercase tracking-wide">Same Day Shipping</span>
              </div>
            </div>
          </div>

          {/* Right Visual (Carousel) */}
          <div className="flex-1 w-full max-w-lg lg:max-w-none relative">
            <div className="relative items-center justify-center flex h-[500px]">
              {/* Slides */}
              {HERO_SLIDES.map((slide, index) => (
                <div
                  key={slide.id}
                  className={`absolute inset-0 transition-all duration-700 ease-in-out transform ${index === currentSlide
                    ? 'opacity-100 translate-x-0 rotate-[-3deg] z-20'
                    : 'opacity-0 translate-x-10 rotate-0 z-0'
                    }`}
                >
                  <div className="relative w-4/5 mx-auto lg:mx-0 rounded-3xl overflow-hidden border border-white/10 shadow-2xl bg-[#111] h-full">
                    <div className="absolute inset-0 bg-gradient-to-tr from-purple-900/40 to-transparent z-10"></div>
                    <img
                      src={slide.image}
                      className="w-full h-full object-cover relative z-0 opacity-90"
                      onError={(e) => { (e.target as HTMLImageElement).src = 'https://placehold.co/600x800?text=Premium+Vape'; }}
                    />

                    {/* Floating Tag */}
                    <div className="absolute bottom-6 left-6 z-20 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl flex items-center gap-3 animate-in fade-in slide-in-from-bottom-4 duration-700">
                      <div className="w-12 h-12 bg-[#aa00ff] rounded-full flex items-center justify-center text-white font-black text-xs shadow-lg shadow-purple-500/50">
                        {slide.profit}
                      </div>
                      <div className="flex flex-col">
                        <span className="text-[10px] uppercase font-bold text-gray-300 tracking-wider">Margin Up To</span>
                        <span className="text-sm font-black text-white">{slide.profit} Profit</span>
                      </div>
                    </div>

                    {/* Top Badge */}
                    <div className="absolute top-6 right-6 z-20 px-3 py-1 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded-full">
                      {slide.badge}
                    </div>
                  </div>
                </div>
              ))}

              {/* Background Decorative Card */}
              <div className="absolute top-10 -right-4 lg:-right-12 w-3/4 h-full bg-[#1a1a1a] rounded-3xl border border-white/5 z-0 transform rotate-[3deg] opacity-50"></div>

              {/* Dots Navigation */}
              <div className="absolute -bottom-12 left-1/2 lg:left-20 transform -translate-x-1/2 flex gap-3 z-30">
                {HERO_SLIDES.map((_, i) => (
                  <button
                    key={i}
                    onClick={() => setCurrentSlide(i)}
                    className={`w-2 h-2 rounded-full transition-all duration-300 ${i === currentSlide ? 'w-8 bg-[#aa00ff]' : 'bg-white/20 hover:bg-white/50'}`}
                  ></button>
                ))}
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  )
}

// 3. FEATURES STRIP
function Features() {
  return (
    <div className="container mx-auto px-4 py-12">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { icon: Truck, title: "Fast Shipping", sub: "2-3 Day Delivery" },
          { icon: ShieldCheck, title: "Authentic Products", sub: "100% Guaranteed" },
          { icon: Zap, title: "Best Prices", sub: "Wholesale Rates" },
          { icon: Package, title: "Bulk Orders", sub: "Huge Discounts" }
        ].map((f, i) => (
          <div key={i} className="flex items-center gap-4 p-4 border border-gray-100 rounded-xl bg-white hover:shadow-lg transition duration-300">
            <div className="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center">
              <f.icon size={20} className="stroke-[2.5]" />
            </div>
            <div>
              <h4 className="font-bold text-gray-900 text-sm">{f.title}</h4>
              <p className="text-xs text-gray-500 font-medium">{f.sub}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

// NEW: VIDEO SHOWCASE (Mixo Banner Only)
function VideoShowcase() {
  return (
    <div className="w-full pb-16">
      <div className="w-full">
        <img src="/media/mixo-banner.gif" className="w-full h-auto object-cover block" alt="Mixo Collection" />
      </div>
    </div>
  )
}



// ProductCard removed - using imported component

// MAIN PAGE
export default function Home() {
  const [appData, setAppData] = useState<any>({ trending: [], new_arrivals: [], best_sellers: [] });
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState<any>(null);

  useEffect(() => {
    const stored = localStorage.getItem('user');
    if (stored) setUser(JSON.parse(stored));

    async function loadHomeData() {
      // ... implementation (this block continues into existing code)
      try {
        // Parallel Fetching for Home Page Sections
        const [trending, newArrivals, bestSellers] = await Promise.all([
          fetchTrendingProducts(),
          fetchNewArrivals(),
          fetchBestSellers()
        ]);

        setAppData({
          trending: Array.isArray(trending) ? trending : [],
          new_arrivals: Array.isArray(newArrivals) ? newArrivals : [],
          best_sellers: Array.isArray(bestSellers) ? bestSellers : [],
          brands: [] // Brands might need a separate fetch if you have an API for it, or hardcode/remove
        });

      } catch (error) {
        console.error("Home Data Load Error:", error);
      } finally {
        setLoading(false);
      }
    }
    loadHomeData();

  }, []);

  // Derived state for cleaner JSX
  const brands = appData.brands || [];
  const products = appData.trending || [];

  return (
    <main className="min-h-screen bg-[#FDFDFD] font-sans selection:bg-purple-100">
      <Navbar />
      <Hero3D products={products} />

      {/* 2. BRANDS MARQUEE (BELOW HERO) - ENHANCED VISUALS */}
      {brands.length > 0 && (
        <div className="relative bg-white py-8 border-b border-gray-100 overflow-hidden group">
          <style jsx>{`
                @keyframes marquee {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                .animate-marquee {
                    animation: marquee 60s linear infinite;
                    display: flex;
                    width: max-content; 
                }
            `}</style>
          <div className="animate-marquee group-hover:[animation-play-state:paused] items-center">
            {[...brands, ...brands, ...brands, ...brands, ...brands, ...brands, ...brands, ...brands].map((b, i) => (
              <div key={i} className="flex-shrink-0 mx-12 grayscale-0 opacity-100 cursor-pointer flex items-center justify-center hover:scale-110 transition duration-300">
                <img src={b.image} alt={b.name} className="h-24 w-auto object-contain max-w-[200px]" />
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 3. VIDEO GRID SECTION (WP CLONE) */}
      <section className="py-4 bg-white">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="aspect-video bg-black rounded-xl overflow-hidden shadow-lg relative h-[200px] md:h-auto">
              <video src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2025/11/Kumi-6-Scenic-video.mp4" className="w-full h-full object-cover" autoPlay loop muted playsInline></video>
            </div>
            <div className="aspect-video bg-black rounded-xl overflow-hidden shadow-lg relative h-[200px] md:h-auto">
              <video src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2025/11/Flum-UT-video-1.mp4" className="w-full h-full object-cover" autoPlay loop muted playsInline></video>
            </div>
            <div className="aspect-video bg-black rounded-xl overflow-hidden shadow-lg relative h-[200px] md:h-auto">
              <video src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2025/11/Nexa-Ultra-II-video.mp4" className="w-full h-full object-cover" autoPlay loop muted playsInline></video>
            </div>
          </div>
        </div>
      </section>

      {/* 4. OFF-STAMP BANNER */}
      <section className="py-6 bg-white">
        <div className="container mx-auto px-4">
          <div className="rounded-xl overflow-hidden w-full">
            <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2025/11/off-stamp-x-cube-banner.webp" className="w-full h-auto object-cover" alt="Off Stamp Banner" />
          </div>
        </div>
      </section>

      {/* 5. TRENDING PRODUCTS */}
      <section className="bg-white py-2">
        <div className="container mx-auto px-4">
          <div className="wcs-rounded-grid-wrap">
            <div className="wcs-section-header" style={{ marginBottom: '20px' }}>
              <h2 className="text-2xl md:text-4xl font-black text-[#111] text-center uppercase tracking-tight">TRENDING PRODUCTS</h2>
              <div style={{ width: '50px', height: '5px', background: '#A101F6', margin: '15px auto 0', borderRadius: '10px' }}></div>
            </div>
            <div className="wcs-rounded-grid">
              {loading ? (
                [1, 2, 3, 4].map(i => <div key={i} className="aspect-[1.15/1] bg-gray-50 animate-pulse rounded-3xl"></div>)
              ) : (
                appData?.trending?.map((p: any) => <WpProductCard key={p.id} p={p} user={user} />)
              )}
            </div>
          </div>
        </div>
      </section>

      {/* 6. NEW ARRIVALS */}
      <section className="bg-white py-2">
        <div className="container mx-auto px-4">
          <div className="wcs-rounded-grid-wrap">
            <div className="wcs-section-header" style={{ marginBottom: '20px' }}>
              <h2 className="text-2xl md:text-4xl font-black text-[#111] text-center uppercase tracking-tight">NEW ARRIVALS</h2>
              <div style={{ width: '50px', height: '5px', background: '#A101F6', margin: '15px auto 0', borderRadius: '10px' }}></div>
            </div>
            <div className="wcs-rounded-grid">
              {loading ? (
                [1, 2, 3, 4].map(i => <div key={i} className="aspect-[1.15/1] bg-gray-50 animate-pulse rounded-3xl"></div>)
              ) : (
                (appData?.new_arrivals || []).map((p: any) => <WpProductCard key={p.id} p={p} user={user} />)
              )}
            </div>
          </div>
        </div>
      </section>

      {/* 7. BEST SELLERS */}
      <section className="bg-white py-2">
        <div className="container mx-auto px-4">
          <div className="wcs-rounded-grid-wrap">
            <div className="wcs-section-header" style={{ marginBottom: '20px' }}>
              <h2 className="text-2xl md:text-4xl font-black text-[#111] text-center uppercase tracking-tight">BEST SELLERS</h2>
              <div style={{ width: '50px', height: '5px', background: '#A101F6', margin: '15px auto 0', borderRadius: '10px' }}></div>
            </div>
            <div className="wcs-rounded-grid">
              {loading ? (
                [1, 2, 3, 4].map(i => <div key={i} className="aspect-[1.15/1] bg-gray-50 animate-pulse rounded-3xl"></div>)
              ) : (
                (appData?.best_sellers || []).map((p: any) => <WpProductCard key={p.id} p={p} user={user} />)
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-black text-white pt-20 pb-10">
        <div className="container mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
          <div className="col-span-1 md:col-span-2">
            <img src="https://mediumturquoise-porcupine-409422.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png" className="h-10 mb-6 bg-white p-2 rounded-lg" />
            <p className="text-gray-400 max-w-sm leading-relaxed">The premier wholesale distributor for vape shop owners. We provide the latest products at the best prices with shipping you can count on.</p>
          </div>
          <div>
            <h4 className="font-bold text-lg mb-6 text-white">Quick Links</h4>
            <ul className="space-y-4 text-gray-400 text-sm">
              <li><Link href="/account" className="hover:text-purple-400 transition">My Account</Link></li>
              <li><Link href="/pact-act" className="hover:text-purple-400 transition italic font-bold text-purple-400">PACT ACT Compliance</Link></li>
              <li><a href="#" className="hover:text-purple-400 transition">Shipping Policy</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-bold text-lg mb-6 text-white">Contact</h4>
            <p className="text-gray-400 text-sm mb-2">support@hypedistribution.com</p>
            <p className="text-gray-400 text-sm">+1 (800) 123-4567</p>
          </div>
        </div>
        <div className="container mx-auto px-6 border-t border-white/10 pt-8 flex justify-between text-xs text-gray-500">
          <p>&copy; 2026 Hype Distribution. Wholesale Only.</p>
          <div className="flex gap-4">
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
          </div>
        </div>
      </footer>
    </main >
  );
}
