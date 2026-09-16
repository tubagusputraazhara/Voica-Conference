import React from 'react';
import { motion } from 'framer-motion';
import { CheckCircleIcon as CheckCircle2, ExclamationCircleIcon as AlertCircle, SignalIcon as Ear, ArrowPathIcon as Loader2, XMarkIcon as X } from '@heroicons/react/24/solid';

export default function VoiceFeedbackLayer({ feedback, transcript, onClear }) {
    if (!feedback) return null;

    const colorMap = {
        listening:  'bg-indigo-600/90 border-indigo-400 text-white',
        processing: 'bg-amber-600/90 border-amber-400 text-white',
        success:    'bg-teal-600/90 border-teal-500 text-white',
        error:      'bg-rose-600/90 border-rose-500 text-white',
    };

    const iconMap = {
        listening: (
            <motion.div animate={{ scale: [1, 1.3, 1] }} transition={{ repeat: Infinity, duration: 1.2 }}>
                <Ear className="w-5 h-5" />
            </motion.div>
        ),
        processing: (
            <motion.div animate={{ rotate: 360 }} transition={{ repeat: Infinity, duration: 1, ease: 'linear' }}>
                <Loader2 className="w-5 h-5" />
            </motion.div>
        ),
        success: <CheckCircle2 className="w-5 h-5" />,
        error: <AlertCircle className="w-5 h-5" />,
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 20, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 20, scale: 0.9, transition: { duration: 0.2 } }}
            className={`fixed bottom-28 lg:bottom-8 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md min-w-[280px] max-w-[90vw] ${colorMap[feedback.type] || colorMap.error}`}
        >
            {iconMap[feedback.type] || iconMap.error}
            <div className="flex-1 min-w-0">
                <span className="font-bold text-sm block">{feedback.message}</span>
                {transcript && feedback.type === 'listening' && (
                    <motion.span key={transcript} initial={{ opacity: 0 }} animate={{ opacity: 0.8 }}
                        className="text-xs opacity-80 block mt-1 truncate">
                        "{transcript}"
                    </motion.span>
                )}
            </div>
            <button onClick={onClear} className="ml-2 hover:bg-white/10 p-1 rounded-full transition-colors flex-shrink-0">
                <X className="w-4 h-4" />
            </button>
        </motion.div>
    );
}
