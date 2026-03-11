import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    domains: ['mediumturquoise-porcupine-409422.hostingersite.com', 'source.unsplash.com', '2z4.30b.myftpupload.com', 'wsrv.nl', 'i0.wp.com'],
  },
  async rewrites() {
    const wpUrl = process.env.NEXT_PUBLIC_WP_URL || 'https://mediumturquoise-porcupine-409422.hostingersite.com';
    return [
      {
        source: '/api/wp/:path*',
        destination: `${wpUrl}/wp-json/:path*`,
      },
    ];
  },
};

export default nextConfig;
