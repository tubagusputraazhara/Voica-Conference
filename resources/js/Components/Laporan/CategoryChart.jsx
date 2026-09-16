import React from 'react';
import { motion } from 'framer-motion';
import { 
    PieChart,
    Pie,
    Cell,
    Tooltip,
    ResponsiveContainer 
} from 'recharts';

export default function CategoryChart({ categoryData, totalPengeluaran }) {
    const COLORS = ['#0d9488', '#0ea5e9', '#6366f1', '#8b5cf6', '#d946ef', '#f43f5e', '#f59e0b'];

    return (
        <motion.div 
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-slate-900 border border-white/5 p-10 rounded-[48px] text-white shadow-3xl overflow-hidden relative"
        >
            <motion.div 
                animate={{ opacity: [0.1, 0.2, 0.1] }}
                transition={{ duration: 10, repeat: Infinity }}
                className="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-[100px]" 
            />
            
            <div className="flex items-center justify-between mb-10 relative z-10">
                <div>
                    <h4 className="text-xl font-bold font-outfit text-white tracking-tight">Allocation</h4>
                    <p className="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] mt-2">Distribusi Per Kategori</p>
                </div>
            </div>
            <div className="h-[250px] w-full relative z-10">
                <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                        <Pie
                            data={categoryData}
                            innerRadius={70}
                            outerRadius={95}
                            paddingAngle={8}
                            dataKey="value"
                            stroke="none"
                        >
                            {categoryData.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                            ))}
                        </Pie>
                        <Tooltip 
                            contentStyle={{ 
                                backgroundColor: '#0f172a',
                                borderRadius: '20px', 
                                border: '1px solid rgba(255,255,255,0.1)', 
                                boxShadow: '0 20px 40px rgba(0,0,0,0.4)',
                                color: '#fff' 
                            }}
                            itemStyle={{ color: '#fff' }}
                            formatter={(val) => `Rp ${new Intl.NumberFormat('id-ID').format(val)}`}
                        />
                    </PieChart>
                </ResponsiveContainer>
            </div>
            <div className="mt-8 space-y-4 relative z-10">
                {categoryData.length > 0 ? categoryData.slice(0, 4).map((item, index) => (
                    <div key={index} className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-2 h-2 rounded-full" style={{ backgroundColor: COLORS[index % COLORS.length] }} />
                            <span className="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">{item.name}</span>
                        </div>
                        <span className="font-outfit font-bold text-teal-400">
                            {((item.value / totalPengeluaran) * 100).toFixed(1)}%
                        </span>
                    </div>
                )) : (
                    <p className="text-center text-slate-500 text-[10px] font-black uppercase tracking-widest py-10">No data available</p>
                )}
            </div>
        </motion.div>
    );
}
