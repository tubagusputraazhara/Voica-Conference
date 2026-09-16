import React, { useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { XMarkIcon as X, CheckIcon as Save, WalletIcon as Wallet, TagIcon as Tag, InformationCircleIcon as Info } from '@heroicons/react/24/solid';

export default function TransactionModal({ 
    isOpen, 
    onClose, 
    data, 
    setData, 
    onSubmit, 
    budgets = [], 
    goals = [] 
}) {
    if (!isOpen) return null;

    return (
        <AnimatePresence>
            <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm">
                <motion.div
                    initial={{ opacity: 0, scale: 0.9, y: 20 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.9, y: 20 }}
                    className="bg-white w-full max-w-xl rounded-[40px] shadow-2xl overflow-hidden"
                >
                    <div className="p-8 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 className="text-2xl font-bold font-outfit">Konfirmasi Transaksi</h3>
                            <p className="text-slate-400 font-medium text-sm">Periksa kembali data dari suara Anda.</p>
                        </div>
                        <button onClick={onClose} className="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400">
                            <X className="w-6 h-6" />
                        </button>
                    </div>

                    <form onSubmit={onSubmit} className="p-8 space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Jenis</label>
                                <select 
                                    value={data.jenis} 
                                    onChange={e => setData({...data, jenis: e.target.value})}
                                    className="w-full h-12 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold"
                                >
                                    <option value="Pengeluaran">🔴 Pengeluaran</option>
                                    <option value="Pemasukan">🟢 Pemasukan</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Kategori</label>
                                <select 
                                    value={data.kategori} 
                                    onChange={e => setData({...data, kategori: e.target.value})}
                                    className="w-full h-12 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold"
                                >
                                    <option value="Makanan">🍔 Makanan</option>
                                    <option value="Transportasi">🚗 Transportasi</option>
                                    <option value="Belanja">🛍️ Belanja</option>
                                    <option value="Hiburan">🎬 Hiburan</option>
                                    <option value="Tagihan">💳 Tagihan</option>
                                    <option value="Lainnya">📝 Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Jumlah (Rp)</label>
                            <div className="relative">
                                <div className="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</div>
                                <input 
                                    type="number"
                                    value={data.jumlah}
                                    onChange={e => setData({...data, jumlah: e.target.value})}
                                    className="w-full h-14 pl-12 pr-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold text-xl"
                                    placeholder="0"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Keterangan</label>
                            <input 
                                type="text"
                                value={data.keterangan}
                                onChange={e => setData({...data, keterangan: e.target.value})}
                                className="w-full h-14 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-semibold"
                                placeholder="Contoh: Makan siang di warteg"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4 pt-2">
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Alokasi Budget</label>
                                <select 
                                    value={data.budget_id || ""} 
                                    onChange={e => setData({...data, budget_id: e.target.value})}
                                    className="w-full h-12 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 text-sm font-semibold"
                                >
                                    <option value="">Pilih Budget</option>
                                    {budgets.map(b => <option key={b.id} value={b.id}>{b.namaBudget}</option>)}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Alokasi Target</label>
                                <select 
                                    value={data.goal_id || ""} 
                                    onChange={e => setData({...data, goal_id: e.target.value})}
                                    className="w-full h-12 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 text-sm font-semibold"
                                >
                                    <option value="">Pilih Target</option>
                                    {goals.map(g => <option key={g.id} value={g.id}>{g.namaGoal}</option>)}
                                </select>
                            </div>
                        </div>

                        <div className="pt-6">
                            <button
                                type="submit"
                                className="w-full h-16 bg-teal-600 text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20"
                            >
                                <Save className="w-5 h-5" />
                                Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </motion.div>
            </div>
        </AnimatePresence>
    );
}
