import React from 'react';

/**
 * MeshGradient — CSS-only animated background (GPU-accelerated, 0 JS overhead)
 * Replaces framer-motion infinite animation which caused GPU overload.
 * Uses transform: translate3d() for hardware-accelerated rendering.
 */
const MeshGradient = () => (
    <div className="fixed inset-0 -z-10 overflow-hidden pointer-events-none bg-slate-50">
        {/* Organic Blurry Orbs — CSS @keyframes via inline style (no JS) */}
        <div
            className="mesh-orb mesh-orb-1 absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-teal-200/30 rounded-full blur-[120px]"
            aria-hidden="true"
        />
        <div
            className="mesh-orb mesh-orb-2 absolute top-[20%] -right-[10%] w-[60%] h-[60%] bg-blue-200/20 rounded-full blur-[150px]"
            aria-hidden="true"
        />
        <div
            className="mesh-orb mesh-orb-3 absolute -bottom-[10%] left-[20%] w-[40%] h-[40%] bg-slate-200/40 rounded-full blur-[100px]"
            aria-hidden="true"
        />

        {/* Noise Texture Overlay — local file to avoid external HTTP request */}
        <div className="absolute inset-0 opacity-[0.03] mix-blend-overlay pointer-events-none noise-texture" aria-hidden="true" />
    </div>
);

export default MeshGradient;
