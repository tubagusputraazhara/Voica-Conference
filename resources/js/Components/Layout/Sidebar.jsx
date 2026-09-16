import React from 'react';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowRightOnRectangleIcon as LogOut } from '@heroicons/react/24/solid';
import { MicrophoneIcon as MicOff } from '@heroicons/react/24/outline';
import { MicrophoneIcon as Mic, BookOpenIcon as BookOpen } from '@heroicons/react/24/solid';

const SidebarItem = ({ href, icon: Icon, label, active }) => (
    <Link 
        href={href}
        className={`flex items-center gap-3 px-4 py-3 rounded-2xl transition-all ${
            active 
            ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/20' 
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
        }`}
    >
        <Icon className="w-[22px] h-[22px]" />
        <span className="font-semibold">{label}</span>
    </Link>
);

export default function Sidebar({ auth, url, menuItems, voiceProps }) {
    const { isSupported, isListening, isProcessing, handleVoiceToggle } = voiceProps;

    return (
        <aside className="w-72 bg-white/70 backdrop-blur-xl border-r border-white/20 hidden lg:flex flex-col p-6 sticky top-0 h-screen z-50">
            <Link href="/dashboard" className="flex items-center justify-center mb-12">
                <img src="/images/voica-logo.png" alt="Voica" className="w-40 h-auto" />
            </Link>

            <nav className="flex-1 space-y-2">
                {menuItems.map((item) => (
                    <SidebarItem key={item.href} {...item} active={url.startsWith(item.href)} />
                ))}
                
                <a 
                    href="/Manual%20Book%20VOICA.pdf"
                    target="_blank"
                    className="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all text-slate-500 hover:bg-slate-100 hover:text-slate-900 mt-2"
                >
                    <BookOpen className="w-[22px] h-[22px]" />
                    <span className="font-semibold">Buku Panduan</span>
                </a>
            </nav>

            {/* Desktop Voice Button */}
            {isSupported && (
                <div className="py-4 border-t border-slate-100">
                    <button
                        onClick={handleVoiceToggle}
                        disabled={isProcessing}
                        className={`w-full flex items-center gap-3 px-4 py-3 rounded-2xl transition-all font-semibold ${
                            isListening
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                                : isProcessing
                                ? 'bg-amber-100 text-amber-600 cursor-wait'
                                : 'text-indigo-600 hover:bg-indigo-50'
                        }`}
                    >
                        <motion.div
                            animate={isListening ? { scale: [1, 1.15, 1] } : {}}
                            transition={{ repeat: Infinity, duration: 1 }}
                        >
                            {isListening ? <MicOff className="w-[22px] h-[22px]" /> : <Mic className="w-[22px] h-[22px]" />}
                        </motion.div>
                        {isListening ? 'Mendengarkan...' : isProcessing ? 'Memproses...' : 'Perintah Suara'}
                    </button>
                </div>
            )}

            <div className="mt-auto pt-6 border-t border-slate-100">
                <div className="flex items-center gap-3 px-2 mb-6">
                    <div className="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-600 font-bold uppercase overflow-hidden">
                        {auth.user.avatar_url ? (
                            <img src={auth.user.avatar_url} alt={auth.user.name} className="w-full h-full object-cover" />
                        ) : (
                            auth.user.name[0]
                        )}
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-bold truncate">{auth.user.name}</p>
                        <p className="text-xs text-slate-400 truncate">{auth.user.email}</p>
                    </div>
                </div>
                <Link 
                    href="/logout" method="post" as="button"
                    className="w-full flex items-center gap-3 px-4 py-3 text-rose-500 hover:bg-rose-50 rounded-2xl transition-all font-semibold"
                >
                    <LogOut className="w-[22px] h-[22px]" />
                    Keluar
                </Link>
            </div>
        </aside>
    );
}
