import React from 'react';
import { motion } from 'framer-motion';
import { ChartBarIcon as BarChart3 } from '@heroicons/react/24/solid';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    Legend, 
    ResponsiveContainer 
} from 'recharts';

export default function TrendChart({ chartData }) {
    return (
        <motion.div 
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.2 }}
            className="lg:col-span-2 bg-white/40 backdrop-blur-xl p-10 rounded-[48px] border border-white/40 shadow-sm"
        >
            <div className="flex items-center justify-between mb-10">
                <div>
                    <h4 className="text-2xl font-bold font-outfit text-slate-900 tracking-tight">Financial Trends</h4>
                    <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Visualisasi arus kas harian</p>
                </div>
                <div className="w-12 h-12 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-600">
                    <BarChart3 className="w-6 h-6" />
                </div>
            </div>
            <div className="h-[350px] w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                        <XAxis 
                            dataKey="date" 
                            axisLine={false} 
                            tickLine={false} 
                            tick={{fill: '#94a3b8', fontSize: 10, fontWeight: '700'}}
                            tickFormatter={(str) => {
                                const d = new Date(str);
                                return `${d.getDate()}/${d.getMonth()+1}`;
                            }}
                        />
                        <YAxis 
                            axisLine={false} 
                            tickLine={false} 
                            tick={{fill: '#94a3b8', fontSize: 10, fontWeight: '700'}}
                            tickFormatter={(val) => `Rp ${val/1000}k`}
                        />
                        <Tooltip 
                            cursor={{fill: 'rgba(20, 184, 166, 0.05)'}}
                            contentStyle={{ 
                                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                backdropFilter: 'blur(10px)',
                                borderRadius: '24px', 
                                border: '1px solid rgba(255,255,255,0.4)', 
                                boxShadow: '0 20px 40px -10px rgba(0,0,0,0.1)' 
                            }}
                            formatter={(val) => `Rp ${new Intl.NumberFormat('id-ID').format(val)}`}
                        />
                        <Legend iconType="circle" />
                        <Bar dataKey="pemasukan" name="Pemasukan" fill="#0d9488" radius={[6, 6, 0, 0]} />
                        <Bar dataKey="pengeluaran" name="Pengeluaran" fill="#f43f5e" radius={[6, 6, 0, 0]} />
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </motion.div>
    );
}
