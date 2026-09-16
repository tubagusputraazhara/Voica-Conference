import React from 'react';
import { useForm } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { XMarkIcon as X, CheckIcon as Save } from '@heroicons/react/24/solid';

export default function BudgetForm({ isFormOpen, setIsFormOpen, periode, editData, setEditData }) {
    const { data, setData, post, put, processing, reset, errors } = useForm({
        namaBudget: '',
        kategori: 'Lainnya',
        jumlah: '',
        periode: periode,
    });

    React.useEffect(() => {
        if (editData) {
            setData({
                namaBudget: editData.namaBudget,
                kategori: editData.kategori,
                jumlah: editData.jumlah,
                periode: editData.periode,
            });
        } else {
            reset();
        }
    }, [editData, isFormOpen]);

    const closeForm = () => {
        setIsFormOpen(false);
        if (setEditData) setEditData(null);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editData) {
            put(`/api/budget/${editData.id}`, {
                onSuccess: () => closeForm(),
            });
        } else {
            post('/api/budget', {
                onSuccess: () => closeForm(),
            });
        }
    };

    const categories = [
        'Makanan', 'Transportasi', 'Hiburan', 'Belanja', 
        'Jalan-Jalan', 'Kesehatan', 'Pendidikan', 'Tagihan', 'Lainnya'
    ];

    return (
        <AnimatePresence>
            {isFormOpen && (
                <>
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={closeForm}
                        className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50"
                    />
                    <motion.div 
                        initial={{ x: '100%' }}
                        animate={{ x: 0 }}
                        exit={{ x: '100%' }}
                        transition={{ type: 'spring', damping: 25, stiffness: 200 }}
                        className="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-50 p-10 overflow-y-auto"
                    >
                        <div className="flex items-center justify-between mb-10">
                            <h2 className="text-3xl font-bold font-outfit">{editData ? 'Edit Budget' : 'Tambah Budget'}</h2>
                            <button onClick={closeForm} className="p-2 hover:bg-slate-100 rounded-full transition-colors">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nama Anggaran</label>
                                <input 
                                    type="text"
                                    value={data.namaBudget}
                                    onChange={e => setData('namaBudget', e.target.value)}
                                    className="w-full h-14 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-semibold"
                                    placeholder="Contoh: Makan Bulanan"
                                    required
                                />
                                {errors.namaBudget && <p className="text-rose-500 text-[10px] font-bold ml-1">{errors.namaBudget}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Kategori</label>
                                <select 
                                    value={data.kategori}
                                    onChange={e => setData('kategori', e.target.value)}
                                    className="w-full h-14 px-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold"
                                    required
                                >
                                    {categories.map(cat => (
                                        <option key={cat} value={cat}>{cat}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Limit Saldo (Rp)</label>
                                <div className="relative">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-lg">Rp</div>
                                    <input 
                                        type="number"
                                        value={data.jumlah}
                                        onChange={e => setData('jumlah', e.target.value)}
                                        className="w-full h-16 pl-12 pr-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold text-2xl"
                                        placeholder="0"
                                        required
                                    />
                                </div>
                                {errors.jumlah && <p className="text-rose-500 text-[10px] font-bold ml-1">{errors.jumlah}</p>}
                            </div>

                            <div className="pt-10">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full h-16 bg-teal-600 text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20"
                                >
                                    <Save className="w-[22px] h-[22px]" />
                                    {editData ? 'Update Anggaran' : 'Simpan Anggaran'}
                                </button>
                            </div>
                        </form>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}
