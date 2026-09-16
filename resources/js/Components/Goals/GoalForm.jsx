import React from 'react';
import { useForm } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { XMarkIcon as X, CheckIcon as Save, ExclamationCircleIcon as AlertCircle } from '@heroicons/react/24/solid';

export default function GoalForm({ isFormOpen, setIsFormOpen, editData, setEditData }) {
    const { data, setData, post, put, processing, reset, errors } = useForm({
        namaGoal: '',
        targetNominal: '',
        nominalBerjalan: 0,
        tanggalTarget: '',
    });

    React.useEffect(() => {
        if (editData) {
            setData({
                namaGoal: editData.namaGoal,
                targetNominal: editData.targetNominal,
                nominalBerjalan: editData.nominalBerjalan,
                tanggalTarget: editData.tanggalTarget,
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
            put(`/goals/${editData.id}`, {
                onSuccess: () => closeForm(),
            });
        } else {
            post('/goals', {
                onSuccess: () => closeForm(),
            });
        }
    };

    return (
        <AnimatePresence>
            {isFormOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={closeForm}
                        className="absolute inset-0 bg-slate-900/40 backdrop-blur-md"
                    />
                    <motion.div 
                        initial={{ opacity: 0, scale: 0.9, y: 20 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: 20 }}
                        className="relative w-full max-w-lg bg-white rounded-[40px] shadow-2xl overflow-hidden"
                    >
                        <div className="p-8 md:p-10 border-b border-slate-50 flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-bold font-outfit">{editData ? 'Edit Target' : 'Target Baru'}</h3>
                                <p className="text-slate-400 font-medium text-sm">Rencanakan kesuksesan Anda hari ini.</p>
                            </div>
                            <button onClick={closeForm} className="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={submit} className="p-8 md:p-10 space-y-6">
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nama Target</label>
                                <input 
                                    type="text"
                                    value={data.namaGoal}
                                    onChange={e => setData('namaGoal', e.target.value)}
                                    className="w-full h-14 px-5 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-semibold transition-all"
                                    placeholder="Contoh: Beli Mobil Baru"
                                    required
                                />
                                {errors.namaGoal && <p className="text-rose-500 text-[10px] font-bold ml-1">{errors.namaGoal}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Target Dana (Rp)</label>
                                    <input 
                                        type="number"
                                        value={data.targetNominal}
                                        onChange={e => setData('targetNominal', e.target.value)}
                                        className="w-full h-14 px-5 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold"
                                        placeholder="0"
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Batas Waktu</label>
                                    <input 
                                        type="date"
                                        value={data.tanggalTarget}
                                        onChange={e => setData('tanggalTarget', e.target.value)}
                                        className="w-full h-14 px-5 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex gap-3">
                                <AlertCircle className="w-[18px] h-[18px] text-amber-600 shrink-0" />
                                <p className="text-[11px] text-amber-800 font-medium leading-relaxed">
                                    Target akan otomatis terhitung dari transaksi yang dialokasikan ke goal ini saat checkout suara.
                                </p>
                            </div>

                            <div className="pt-6">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full h-16 bg-teal-600 text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20"
                                >
                                    <Save className="w-5 h-5" />
                                    {editData ? 'Update Target' : 'Mulai Target'}
                                </button>
                            </div>
                        </form>
                    </motion.div>
                </div>
            )}
        </AnimatePresence>
    );
}
