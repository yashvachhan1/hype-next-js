'use client';

import React from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { EffectCoverflow, Pagination, Navigation, Autoplay } from 'swiper/modules';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/effect-coverflow';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

const FALLBACK_SLIDES = [
    { id: 1, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading' },
    { id: 2, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading' },
    { id: 3, image: 'https://placehold.co/600x600?text=Loading...', name: 'Loading' },
];

export default function Hero3D({ products }: { products: any[] }) {
    const [activeIndex, setActiveIndex] = React.useState(0);

    // Ensure we have enough slides for a seamless infinite loop by repeating the data
    const rawSlides = (products && products.length > 0) ? products : FALLBACK_SLIDES;
    // Trip the array 8 times to ensure no empty space on wide screens
    const slides = [...rawSlides, ...rawSlides, ...rawSlides, ...rawSlides, ...rawSlides, ...rawSlides, ...rawSlides, ...rawSlides];
    const originalCount = rawSlides.length;

    return (
        <section className="w-full bg-[#f8f9fa] py-4 relative overflow-hidden">
            <div className="container mx-auto">
                <div className="text-center mb-0">
                    {/* Header Removed as requested */}
                </div>

                <div className="hero-3d-wrapper relative">
                    <Swiper
                        key={slides.length} // Force re-render when data loads
                        effect={'coverflow'}
                        grabCursor={true}
                        centeredSlides={true}
                        slidesPerView={'auto'}
                        initialSlide={originalCount * 2} // Start safely in the middle
                        loop={true}
                        speed={1000}
                        autoplay={{
                            delay: 4000,
                            disableOnInteraction: false,
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
                            slideShadows: false,
                        }}
                        pagination={false} // Disable default dots
                        modules={[EffectCoverflow, Autoplay]}
                        className="mySwiper"
                    >
                        {slides.map((p, index) => (
                            <SwiperSlide key={`${p.id}-${index}`} className="w-[300px] sm:w-[400px] md:w-[500px] aspect-square relative rounded-3xl overflow-hidden transition-all duration-500 bg-white">
                                <img
                                    src={p.image}
                                    alt={p.name}
                                    className="w-full h-full object-cover block"
                                    onError={(e) => {
                                        (e.target as HTMLImageElement).src = `https://placehold.co/800x800/eee/333?text=Product`;
                                    }}
                                />
                                <div className="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/60 to-transparent p-6 pt-20">
                                    <h3 className="text-white text-lg font-bold uppercase truncate">{p.name}</h3>
                                </div>
                            </SwiperSlide>
                        ))}
                    </Swiper>

                    {/* CUSTOM PAGINATION (DASHES) */}
                    <div className="flex justify-center gap-2 mt-4">
                        {Array.from({ length: originalCount }).map((_, i) => (
                            <div
                                key={i}
                                className={`h-1.5 rounded-full transition-all duration-300 ${i === activeIndex
                                    ? 'w-8 bg-[#A101F6]' // Active: Long Purple Dash
                                    : 'w-4 bg-gray-300'  // Inactive: Short Gray Dash
                                    }`}
                            ></div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Custom Styles without Borders */}
            <style jsx global>{`
                .swiper {
                    width: 100%;
                    padding-top: 10px;
                    padding-bottom: 30px;
                }
                .swiper-slide {
                    background-position: center;
                    background-size: cover;
                    width: 300px; 
                    height: 300px;
                    /* Updated shadow instead of border */
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
                }
                @media (min-width: 640px) {
                    .swiper-slide { width: 400px; height: 400px; }
                }
                @media (min-width: 768px) {
                    .swiper-slide { width: 500px; height: 500px; }
                }
                
                .swiper-pagination-bullet-active {
                    background: #A101F6 !important;
                }
            `}</style>
        </section>
    );
}
