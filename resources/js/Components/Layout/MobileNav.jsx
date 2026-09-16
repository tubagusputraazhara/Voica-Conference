import React from 'react';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { MicrophoneIcon as MicOff } from '@heroicons/react/24/outline';
import { MicrophoneIcon as Mic, ArrowPathIcon as Loader2 } from '@heroicons/react/24/solid';

export default function MobileNav({ url, menuItems, voiceProps }) {
    const { isSupported, isListening, isProcessing, handleVoiceToggle } = voiceProps;

    const renderNavItem = (item) => {
        const Icon = item.icon;
        const active = url.startsWith(item.href);
        return (
            <Link key={item.href} href={item.href}
                className={`flex flex-col items-center gap-1 pt-2 pb-2 min-w-[56px] transition-colors ${active ? 'text-teal-600' : 'text-slate-400'}`}>
                <Icon className={`w-[22px] h-[22px] ${active ? 'stroke-2' : ''}`} />
                <span className="text-[10px] font-semibold">{item.label}</span>
            </Link>
        );
    };

    return (
        <nav className="lg:hidden fixed bottom-0 w-full bg-white/95 backdrop-blur-lg border-t border-slate-100 z-40"
             style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}>
            <div className="flex items-end justify-around h-20 px-4 relative">
                {renderNavItem(menuItems[0])}
                {renderNavItem(menuItems[1])}

                {/* Center: Voice Button */}
                {isSupported && (
                    <div className="flex flex-col items-center -mt-7 px-1">
                        <motion.button
                            onClick={handleVoiceToggle}
                            disabled={isProcessing}
                            whileTap={{ scale: 0.9 }}
                            className={`relative w-[56px] h-[56px] rounded-full flex items-center justify-center transition-all border-4 border-white ${
                                isListening
                                    ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/40'
                                    : isProcessing
                                    ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30 cursor-wait'
                                    : 'bg-gradient-to-br from-teal-500 to-teal-600 text-white shadow-lg shadow-teal-600/30'
                            }`}
                        >
                            {isListening && (
                                <motion.span
                                    className="absolute inset-0 rounded-full border-2 border-teal-400"
                                    animate={{ scale: [1, 1.6, 1.6], opacity: [0.5, 0, 0] }}
                                    transition={{ repeat: Infinity, duration: 1.5, ease: 'easeOut' }}
                                />
                            )}
                            <motion.div
                                animate={isListening ? { scale: [1, 1.15, 1] } : {}}
                                transition={{ repeat: Infinity, duration: 0.8 }}
                            >
                                {isProcessing ? <Loader2 className="w-6 h-6 animate-spin" /> : isListening ? <MicOff className="w-6 h-6" /> : <Mic className="w-6 h-6" />}
                            </motion.div>
                        </motion.button>
                        <span className={`text-[10px] font-semibold mt-0.5 ${
                            isListening ? 'text-indigo-600' : isProcessing ? 'text-amber-600' : 'text-slate-400'
                        }`}>
                            {isProcessing ? '...' : isListening ? 'Stop' : 'Suara'}
                        </span>
                    </div>
                )}

                {renderNavItem(menuItems[2])}
                {renderNavItem(menuItems[3])}
            </div>
        </nav>
    );
}
