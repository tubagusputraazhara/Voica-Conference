import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthLayout from '../Layouts/AuthLayout';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    WalletIcon as Wallet, 
    PlusIcon as Plus, 
    CalendarIcon as Calendar, 
    ChevronRightIcon as ChevronRight, 
    PencilSquareIcon as EditIcon, 
    ArrowUpCircleIcon as ArrowUpCircle,
    ArrowDownCircleIcon as ArrowDownCircle,
    BanknotesIcon as PiggyBank
} from '@heroicons/react/24/solid';
import BudgetForm from '../Components/Budgeting/BudgetForm';

const BudgetCard = ({ budget, onEdit }) => {
    const isWarning = budget.persentase > 80;
    const isDanger = budget.persentase >= 100;

    return (
        <motion.div 
            variants={{
                hidden: { opacity: 0, y: 20 },
                visible: { opacity: 1, y: 0 }
            }}
            whileHover={{ 
                scale: 1.02, 
                y: -5,
                boxShadow: "0 20px 40px rgba(0,0,0,0.05)"
            }}
            className="relative group bg-white/40 backdrop-blur-xl p-7 rounded-[32px] border border-white/40 shadow-sm flex flex-col justify-between h-full overflow-hidden"
        >
            {/* Inner Glow Decorative Element */}
            <div className={`absolute -right-4 -top-4 w-24 h-24 rounded-full blur-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 ${isDanger ? 'bg-rose-500' : isWarning ? 'bg-amber-500' : 'bg-teal-500'}`} />

            <div className="flex items-center justify-between mb-8 relative z-10">
                <div className="flex items-center gap-5">
                    <div className="w-14 h-14 bg-white shadow-inner rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-500">
                        {budget.icon || '💰'}
                    </div>
                    <div>
                        <h4 className="font-bold text-slate-900 text-lg tracking-tight">{budget.namaBudget}</h4>
                        <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{budget.kategori}</p>
                    </div>
                </div>
                <button onClick={(e) => { e.preventDefault(); e.stopPropagation(); onEdit(budget); }} className="p-2 text-teal-600 bg-teal-50 hover:bg-teal-100 rounded-full transition-all z-20 flex items-center gap-1 px-3">
                    <EditIcon className="w-4 h-4" />
                    <span className="text-xs font-bold">Edit</span>
                </button>
            </div>

            <div className="space-y-6 relative z-10">
                <div className="flex items-end justify-between">
                    <div>
                        <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Terpakai</p>
                        <p className="font-bold text-xl text-slate-900">{budget.terpakai_formatted}</p>
                    </div>
                    <div className="text-right">
                        <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Limit</p>
                        <p className="font-bold text-slate-400">{budget.jumlah_formatted}</p>
                    </div>
                </div>

                <div className="relative h-2.5 bg-slate-200/50 rounded-full overflow-hidden">
                    <motion.div 
                        initial={{ width: 0 }}
                        animate={{ width: `${Math.min(budget.persentase, 100)}%` }}
                        transition={{ duration: 1.5, ease: "easeOut" }}
                        className={`absolute h-full rounded-full ${
                            isDanger ? 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.4)]' : isWarning ? 'bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-teal-500 shadow-[0_0_10px_rgba(20,184,166,0.4)]'
                        }`}
                    />
                </div>

                <div className="flex items-center justify-between pt-2">
                    <span className={`text-xs font-black uppercase tracking-widest ${
                        isDanger ? 'text-rose-600' : isWarning ? 'text-amber-600' : 'text-teal-600'
                    }`}>
                        {budget.persentase}% Terpakai
                    </span>
                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-100 px-3 py-1 rounded-full">
                        Sisa {budget.sisa_formatted}
                    </span>
                </div>
            </div>
        </motion.div>
    );
};

export default function Budgeting({ budgetsWithProgress, periode, allBudgets, goals }) {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editData, setEditData] = useState(null);

    const handleEdit = (budget) => {
        setEditData(budget);
        setIsFormOpen(true);
    };

    const handleOpenForm = () => {
        setEditData(null);
        setIsFormOpen(true);
    };

    return (
        <AuthLayout>
            <Head title="Manajemen Anggaran" />

            <div className="max-w-7xl mx-auto">
                {/* Header Area */}
                <motion.div 
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12"
                >
                    <div>
                        <h1 className="text-5xl font-bold font-outfit mb-3 tracking-tighter text-slate-900">Anggaran</h1>
                        <p className="text-slate-500 font-medium">Atur dan pantau perencanaan finansial Anda dengan presisi.</p>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="bg-white/40 backdrop-blur-md border border-white/40 px-5 py-3 rounded-2xl flex items-center gap-3 shadow-sm group hover:border-teal-500/30 transition-all">
                            <Calendar className="w-[18px] h-[18px] text-teal-600 group-hover:scale-110 transition-transform" />
                            <input 
                                type="month" 
                                value={periode}
                                onChange={(e) => router.get('/budgeting', { periode: e.target.value }, { preserveState: true })}
                                className="border-0 p-0 text-sm font-black uppercase tracking-widest focus:ring-0 bg-transparent text-slate-700"
                            />
                        </div>
                        <motion.button 
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            onClick={handleOpenForm}
                            className="h-14 px-8 bg-teal-600 text-white rounded-2xl flex items-center justify-center gap-3 font-bold shadow-xl shadow-teal-600/30 hover:bg-teal-700 transition-all"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Buat Budget</span>
                        </motion.button>
                    </div>
                </motion.div>

                {/* Summary Cards */}
                <motion.div 
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.2 }}
                    className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12"
                >
                    <div className="bg-slate-900 p-7 rounded-[40px] text-white shadow-2xl shadow-slate-900/20 flex items-center gap-6 group relative overflow-hidden">
                        <motion.div 
                            animate={{ opacity: [0.1, 0.2, 0.1] }}
                            transition={{ duration: 5, repeat: Infinity }}
                            className="absolute -right-4 -top-4 w-32 h-32 bg-teal-500 rounded-full blur-3xl" 
                        />
                        <div className="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md relative z-10 group-hover:scale-110 transition-transform shadow-inner">
                            <Wallet className="w-8 h-8 text-teal-400" />
                        </div>
                        <div className="relative z-10">
                            <p className="text-white/40 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Total Alokasi</p>
                            <h3 className="text-3xl font-bold font-outfit tracking-tight">
                                <span className="text-sm font-normal text-white/30 mr-1">Rp</span>
                                {new Intl.NumberFormat('id-ID').format(budgetsWithProgress.reduce((acc, b) => acc + b.jumlah, 0))}
                            </h3>
                        </div>
                    </div>
                    <div className="bg-white/40 backdrop-blur-xl p-7 rounded-[40px] border border-white/40 shadow-sm flex items-center gap-6 group">
                        <div className="w-16 h-16 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-500 group-hover:scale-110 transition-transform shadow-inner">
                            <ArrowDownCircle className="w-8 h-8" />
                        </div>
                        <div>
                            <p className="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Total Terpakai</p>
                            <h3 className="text-3xl font-bold font-outfit text-slate-900 tracking-tight">
                                <span className="text-sm font-normal text-slate-400 mr-1">Rp</span>
                                {new Intl.NumberFormat('id-ID').format(budgetsWithProgress.reduce((acc, b) => acc + b.terpakai, 0))}
                            </h3>
                        </div>
                    </div>
                    <div className="bg-white/40 backdrop-blur-xl p-7 rounded-[40px] border border-white/40 shadow-sm flex items-center gap-6 group">
                        <div className="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-600 group-hover:scale-110 transition-transform shadow-inner">
                            <PiggyBank className="w-8 h-8" />
                        </div>
                        <div>
                            <p className="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Sisa Anggaran</p>
                            <h3 className="text-3xl font-bold font-outfit text-slate-900 tracking-tight">
                                <span className="text-sm font-normal text-slate-400 mr-1">Rp</span>
                                {new Intl.NumberFormat('id-ID').format(budgetsWithProgress.reduce((acc, b) => acc + b.sisa, 0))}
                            </h3>
                        </div>
                    </div>
                </motion.div>

                {/* Budget Grid */}
                {budgetsWithProgress.length > 0 ? (
                    <motion.div 
                        initial="hidden"
                        animate="visible"
                        variants={{
                            visible: {
                                transition: {
                                    staggerChildren: 0.1
                                }
                            }
                        }}
                        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                    >
                        {budgetsWithProgress.map((budget) => (
                            <BudgetCard key={budget.id} budget={budget} onEdit={handleEdit} />
                        ))}
                    </motion.div>
                ) : (
                    <div className="bg-white rounded-[40px] p-20 text-center border-2 border-dashed border-slate-200">
                        <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <Wallet className="w-10 h-10 text-slate-200" />
                        </div>
                        <h3 className="text-xl font-bold text-slate-900 mb-2">Belum Ada Anggaran</h3>
                        <p className="text-slate-500 max-w-sm mx-auto mb-8">Anda belum memiliki rencana pengeluaran untuk periode ini. Mulai kelola uang Anda sekarang.</p>
                        <button 
                            onClick={handleOpenForm}
                            className="px-8 py-4 bg-teal-600 text-white rounded-2xl font-bold shadow-lg shadow-teal-600/20 hover:bg-teal-700 transition-all"
                        >
                            Buat Anggaran Pertama
                        </button>
                    </div>
                )}
            </div>

            {/* Slide-over Form Overlay */}
            <BudgetForm isFormOpen={isFormOpen} setIsFormOpen={setIsFormOpen} periode={periode} editData={editData} setEditData={setEditData} />
        </AuthLayout>
    );
}
