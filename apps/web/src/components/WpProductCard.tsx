
import Link from 'next/link';
import { Heart } from 'lucide-react';

// EXACT CSS FROM WORDPRESS (Sanitized for React)
const WP_STYLES = `
    .wcs-rounded-grid-wrap { width: 100%; padding: 40px 0; font-family: 'Inter', sans-serif; }
    .wcs-rounded-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }

    .wcs-rounded-card { 
        background: #fff; 
        border-radius: 24px; 
        padding: 0; /* Removed padding to allow full-bleed image */
        text-align: center; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        transition: all 0.35s ease;
        display: flex;
        flex-direction: column;
        border: none; /* REMOVED BORDER */
        overflow: hidden; /* Ensures child elements (img) stay inside rounded corners */
    }
    .wcs-rounded-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }

    /* Full-Bleed Media Section */
    .wcs-rc-media { 
        position: relative; 
        width: 100%; 
        aspect-ratio: 1.15/1; /* Slightly horizontal/landscape layout to match image */
        background: #fff; 
        overflow: hidden; 
        margin: 0; /* No space around image */
    }
    .wcs-rc-img-link { display: block; width: 100%; height: 100%; position: relative; }
    
    .wcs-rc-media img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover !important; /* Forces image to fill the area */
        display: block; 
        transition: opacity 0.5s ease;
    }

    /* Hover Photo Change Logic */
    .wcs-hover-img { 
        position: absolute; 
        top: 0; left: 0; 
        opacity: 0; 
        z-index: 2;
    }
    .wcs-rounded-card:hover .wcs-hover-img { opacity: 1; }
    .wcs-rounded-card:hover .wcs-main-img { opacity: 0; }

    /* Content Area with Padding */
    .wcs-rc-content { padding: 25px 20px 25px 20px; flex: 1; display: flex; flex-direction: column; align-items: center; }
    
    .wcs-rc-brand { font-size: 11px; font-weight: 700; color: #A101F6; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }

    .wcs-rc-title { font-size: 15px; font-weight: 700; color: #111; margin: 0 0 12px 0; line-height: 1.4; min-height: 42px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .wcs-rc-title a { text-decoration: none; color: inherit; }

    .wcs-rc-price-box { margin-bottom: 20px; min-height: 30px; display: flex; align-items: center; justify-content: center; }
    .wcs-rc-price { font-size: 20px; font-weight: 800; color: #A101F6; }
    
    .wcs-rc-lock-msg { font-size: 13px; font-weight: 600; color: #444; }
    .wcs-rc-lock-msg a { color: #ff0000; text-decoration: none; font-weight: 800; }

    .wcs-rc-btn { 
        display: block; width: 100%; background: #A101F6; color: #fff !important; 
        padding: 12px 0; border-radius: 12px; font-size: 12px; font-weight: 800; 
        text-transform: uppercase; text-decoration: none; letter-spacing: 0.5px;
        transition: background 0.3s;
    }
    .wcs-rc-btn:hover { background: #8e00d6; }

    @media (max-width: 1024px) { 
        .wcs-rounded-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; } 
        .wcs-rc-price { font-size: 16px; }
        .wcs-rc-title { font-size: 13px; min-height: 36px; }
        .wcs-rc-content { padding: 15px 12px; }
    }
    @media (max-width: 640px) { 
        .wcs-rounded-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } 
        .wcs-rc-price { font-size: 14px; }
        .wcs-rc-content { padding: 12px 8px; }
        .wcs-rc-btn { font-size: 10px; padding: 10px 0; }
    }
`;

export default function WpProductCard({ p, user }: { p: any, user: any }) {
    const imageSrc = p.image || 'https://placehold.co/400x400/png?text=No+Image';
    const hoverImageSrc = (p.gallery && p.gallery.length > 0) ? p.gallery[0] : imageSrc;

    return (
        <>
            <style jsx global>{WP_STYLES}</style>

            <div className="wcs-rounded-card">
                <div className="wcs-rc-media">
                    <Link href={`/product/${p.slug}`} className="wcs-rc-img-link">
                        <img src={imageSrc} className="wcs-main-img" alt={p.name} />
                        <img src={hoverImageSrc} className="wcs-hover-img" alt={`${p.name} Hover`} />
                    </Link>
                </div>

                <div className="wcs-rc-content">
                    <span className="wcs-rc-brand">{p.brand || 'Premium Brand'}</span>
                    <h4 className="wcs-rc-title">
                        <Link href={`/product/${p.slug}`}>{p.name}</Link>
                    </h4>

                    <div className="wcs-rc-price-box">
                        {user ? (
                            <div className="flex flex-col items-center">
                                {/* WHOLESALE LOGIC */}
                                {(user?.role && (user.role.includes('wholesale') || user.role.includes('customer_wholesale')) && p.wholesale_price) ? (
                                    <>
                                        <span className="text-[10px] text-purple-600 font-bold uppercase tracking-widest mb-1">Wholesale</span>
                                        <span className="wcs-rc-price">${Number(p.wholesale_price).toFixed(2)}</span>
                                    </>
                                ) : (p.raw_price || p.regular_price) ? (
                                    <span className="wcs-rc-price">${Number(p.raw_price || p.regular_price).toFixed(2)}</span>
                                ) : (
                                    <span className="wcs-rc-price" dangerouslySetInnerHTML={{ __html: p.price_html || p.price }}></span>
                                )}
                            </div>
                        ) : (
                            <div className="wcs-rc-lock-msg">
                                Please <Link href="/login">log in</Link> to view price
                            </div>
                        )}
                    </div>

                    <Link href={`/product/${p.slug}`} className="wcs-rc-btn">VIEW DETAILS</Link>
                </div>
            </div>
        </>
    );
}
