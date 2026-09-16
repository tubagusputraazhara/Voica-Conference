import React from 'react';
import { motion } from 'framer-motion';

export default function SettingsSection({ title, description, children, icon: Icon }) {
    return (
        <motion.div 
            variants={{
                hidden: { opacity: 0, y: 20 },
                visible: { opacity: 1, y: 0 }
            }}
            className="bg-white/40 backdrop-blur-xl rounded-[48px] border border-white/40 shadow-sm overflow-hidden mb-10"
        >
            <div className="p-10 border-b border-white/40 flex items-center justify-between">
                <div className="flex items-center gap-6">
                    <div className="w-14 h-14 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-600 shadow-inner">
                        <Icon size={28} />
                    </div>
                    <div>
                        <h4 className="text-xl font-bold text-slate-900 tracking-tight">{title}</h4>
                        <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">{description}</p>
                    </div>
                </div>
            </div>
            <div className="p-10">
                {children}
            </div>
        </motion.div>
    );
}
