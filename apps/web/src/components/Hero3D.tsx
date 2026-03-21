'use client';

import React, { useEffect, useState } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { EffectCoverflow, Autoplay } from 'swiper/modules';
import Link from 'next/link';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/effect-coverflow';

const FALLBACK_SLIDES = [
    { id: 1, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading', slug: '#' },
    { id: 2, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading', slug: '#' },
    { id: 3, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading', slug: '#' },
];

export default function Hero3D({ products }: { products: any[] }) {
    const [activeIndex, setActiveIndex] = useState(0);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    // Ensure we have enough slides for a seamless infinite loop by repeating the data
    const rawSlides = (products && products.length > 0) ? products : FALLBACK_SLIDES;
    
    // Duplicate slides to ensure smooth loop (PHP duplicates x8 or 12)
    let slides: any[] = [];
    const duplicationFactor = rawSlides.length < 3 ? 12 : 8;
    for (let i = 0; i < duplicationFactor; i++) {
        slides = [...slides, ...rawSlides];
    }
    
    const originalCount = rawSlides.length;

    // FOUC prevention (matching PHP visibility logic)
    if (!mounted) return <div className="w-full bg-transparent min-h-[400px]"></div>;

    return (
        <section className="w-full bg-transparent py-5 relative overflow-hidden font-sans">
            <div className="w-full max-w-full mx-auto text-center">
                
                <div className="relative w-full pb-[50px]">
                    <Swiper
                        key={slides.length} // Force re-render when data loads
                        effect={'coverflow'}
                        grabCursor={true}
                        centeredSlides={true}
                        slidesPerView={'auto'}
                        initialSlide={originalCount * 2} // Start safely in the middle
                        loop={true}
                        speed={2700} // Exactly matching PHP
                        autoplay={{
                            delay: 1000,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true, // Matching PHP
                        }}
                        onSlideChange={(swiper) => {
                            // Calculate effective index based on original count
                            setActiveIndex(swiper.realIndex % originalCount);
                        }}
                        coverflowEffect={{
                            rotate: 35,
                            stretch: 0,
                            depth: 150,
                            modifier: 1,
                            slideShadows: false, // Match PHP
                        }}
                        modules={[EffectCoverflow, Autoplay]}
                        className="myHeroSwiper"
                    >
                        {slides.map((p, index) => (
                            <SwiperSlide key={`${p.id}-${index}`} className="group relative rounded-[24px] bg-white overflow-hidden shadow-[0_10px_40px_rgba(0,0,0,0.1)] transition-all duration-500">
                                <Link href={p.slug ? `/product/${p.slug}` : '#'} className="block w-full h-full">
                                    {/* Image with zoom on active slide */}
                                    <img
                                        src={p.image}
                                        alt={p.name}
                                        className="w-full h-full object-cover block transition-transform duration-[600ms] wcs-slide-img"
                                        onError={(e) => {
                                            (e.target as HTMLImageElement).src = `https://placehold.co/800x800/eee/333?text=Product`;
                                        }}
                                    />
                                    
                                    {/* Gradient overlay and title, visible mainly on active slide */}
                                    <div className="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 to-transparent p-[30px] pt-[100px] text-left opacity-0 translate-y-[20px] transition-all duration-400 wcs-slide-overlay">
                                        <h3 className="text-white text-[24px] font-[800] uppercase m-0 leading-[1.2] drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)] wcs-slide-title">{p.name}</h3>
                                    </div>
                                </Link>
                            </SwiperSlide>
                        ))}
                    </Swiper>

                    {/* CUSTOM PAGINATION (DASHES) */}
                    <div className="flex justify-center gap-2 mt-[30px]">
                        {Array.from({ length: originalCount }).map((_, i) => (
                            <div
                                key={i}
                                className={`h-[6px] rounded-full transition-all duration-300 cursor-pointer ${
                                    i === activeIndex
                                    ? 'w-[32px] bg-gradient-to-r from-[#8b5cf6] to-[#d946ef]' // Active dash (Violet to Fuchsia)
                                    : 'w-[12px] bg-gray-300'  // Inactive short dash
                                    }`}
                            ></div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Custom Styles matching PHP Template exact boundaries */}
            <style jsx global>{`
                .myHeroSwiper {
                    opacity: 1;
                    transition: opacity 0.5s ease;
                }
                .swiper-slide {
                    width: 300px;
                    height: 300px;
                }
                
                @media (min-width: 640px) {
                    .swiper-slide { width: 400px; height: 400px; }
                }
                @media (min-width: 1024px) {
                    .swiper-slide { width: 500px; height: 500px; }
                }

                /* Active state styles from PHP */
                .swiper-slide-active .wcs-slide-img {
                    transform: scale(1.05);
                }
                .swiper-slide-active .wcs-slide-overlay {
                    opacity: 1 !important;
                    transform: translateY(0) !important;
                }
            `}</style>
        </section>
    );
}
