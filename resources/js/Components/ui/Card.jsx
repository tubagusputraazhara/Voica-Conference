import React from 'react';
import { motion } from 'framer-motion';

export const Card = ({ children, className = '', noPadding = false, ...props }) => {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className={`bg-white/80 backdrop-blur-xl rounded-2xl border border-white/40 shadow-[0_8px_32px_rgba(0,0,0,0.05)] overflow-hidden ${className}`}
            {...props}
        >
            {noPadding ? children : <div className="p-6">{children}</div>}
        </motion.div>
    );
};

export const CardHeader = ({ title, icon: Icon, action, className = '' }) => {
    return (
        <div className={`p-6 border-b border-gray-100 flex items-center justify-between ${className}`}>
            <div className="flex items-center gap-3">
                {Icon && (
                    <div className="p-2 bg-indigo-50 rounded-xl">
                        <Icon className="w-5 h-5 text-indigo-600" />
                    </div>
                )}
                <h3 className="font-semibold text-gray-800">{title}</h3>
            </div>
            {action && <div>{action}</div>}
        </div>
    );
};

export const CardContent = ({ children, className = '' }) => {
    return <div className={`p-6 ${className}`}>{children}</div>;
};

export default Card;
