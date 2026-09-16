import React, { useState, useCallback } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    Squares2X2Icon as LayoutDashboard, 
    WalletIcon as Wallet, 
    TrophyIcon as Target, 
    ChartBarIcon as BarChart3, 
    Cog6ToothIcon as Settings, 
    CheckCircleIcon as CheckCircle2,
    ExclamationCircleIcon as AlertCircle,
    XMarkIcon as X
} from '@heroicons/react/24/solid';
import MeshGradient from '../Components/MeshGradient';
import TransactionModal from '../Components/TransactionModal';
import { useVoiceCommand } from '../Hooks/useVoiceCommand';
import { fetchTransactionModalDependencies, submitVoiceTransaction } from '../api/voiceApi';

import VoiceFeedbackLayer from '../Components/Voice/VoiceFeedbackLayer';
import Sidebar from '../Components/Layout/Sidebar';
import MobileNav from '../Components/Layout/MobileNav';

// ─── Toast Component ─────────────────────────────────────────────────
const Toast = ({ type, message, onClear }) => (
    <motion.div
        initial={{ opacity: 0, y: -20, scale: 0.9 }}
        animate={{ opacity: 1, y: 0, scale: 1 }}
        exit={{ opacity: 0, scale: 0.9, transition: { duration: 0.2 } }}
        className={`fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md ${
            type === 'success' 
            ? 'bg-teal-600/90 border-teal-500 text-white' 
            : 'bg-rose-600/90 border-rose-500 text-white'
        }`}
    >
        {type === 'success' ? <CheckCircle2 className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
        <span className="font-bold text-sm">{message}</span>
        <button onClick={onClear} className="ml-2 hover:bg-white/10 p-1 rounded-full transition-colors">
            <X className="w-4 h-4" />
        </button>
    </motion.div>
);

// ─── Main Layout ─────────────────────────────────────────────────────
export default function AuthLayout({ children }) {
    const { url, props } = usePage();
    const { auth, flash } = props;
    const [showToast, setShowToast] = useState(true);

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [transactionData, setTransactionData] = useState({
        jenis: 'Pengeluaran',
        kategori: 'Lainnya',
        jumlah: 0,
        keterangan: '',
        budget_id: null,
        goal_id: null,
    });
    const [modalBudgets, setModalBudgets] = useState([]);
    const [modalGoals, setModalGoals] = useState([]);

    const onTransactionParsed = useCallback(async (data) => {
        try {
            const deps = await fetchTransactionModalDependencies();
            setModalBudgets(deps.budgets);
            setModalGoals(deps.goals);
        } catch (e) {
            console.warn('Could not fetch budgets/goals for modal:', e);
        }
        setTransactionData(data);
        setIsModalOpen(true);
    }, []);

    const { 
        isListening, isSupported, isProcessing,
        transcript, feedback,
        startListening, stopListening, clearFeedback 
    } = useVoiceCommand({
        onTransactionParsed
    });

    const handleSubmitTransaction = async (e) => {
        e.preventDefault();
        try {
            await submitVoiceTransaction(transactionData);
            setIsModalOpen(false);
            router.reload({ only: ['stats', 'recentTransactions', 'budgetsWithProgress', 'goals', 'allBudgets'] });
        } catch (err) {
            console.error('Transaction save error:', err);
        }
    };

    React.useEffect(() => {
        if (flash?.success || flash?.error) {
            setShowToast(true);
            const t = setTimeout(() => setShowToast(false), 5000);
            return () => clearTimeout(t);
        }
    }, [flash]);

    const menuItems = [
        { href: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
        { href: '/budgeting', icon: Wallet, label: 'Anggaran' },
        { href: '/goals', icon: Target, label: 'Target' },
        { href: '/laporan', icon: BarChart3, label: 'Laporan' },
        { href: '/settings', icon: Settings, label: 'Pengaturan' },
    ];

    const handleVoiceToggle = () => {
        if (isListening) stopListening();
        else startListening();
    };

    const voiceProps = { isSupported, isListening, isProcessing, handleVoiceToggle };

    return (
        <div className="min-h-screen flex relative">
            <MeshGradient />

            <AnimatePresence>
                {showToast && flash?.success && (
                    <Toast type="success" message={flash.success} onClear={() => setShowToast(false)} />
                )}
                {showToast && flash?.error && (
                    <Toast type="error" message={flash.error} onClear={() => setShowToast(false)} />
                )}
            </AnimatePresence>

            <AnimatePresence>
                {feedback && (
                    <VoiceFeedbackLayer
                        feedback={feedback}
                        transcript={transcript}
                        onClear={() => { clearFeedback(); stopListening(); }}
                    />
                )}
            </AnimatePresence>

            <TransactionModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                data={transactionData}
                setData={setTransactionData}
                onSubmit={handleSubmitTransaction}
                budgets={modalBudgets}
                goals={modalGoals}
            />

            <Sidebar auth={auth} url={url} menuItems={menuItems} voiceProps={voiceProps} />

            <main className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Mobile Header */}
                <header className="lg:hidden h-20 bg-white/80 backdrop-blur-md border-b border-white/20 flex items-center justify-between px-6 sticky top-0 z-40">
                    <Link href="/dashboard" className="flex items-center">
                        <img src="/images/voica-logo.png" alt="Voica" className="w-36 h-auto" />
                    </Link>
                    <Link href="/settings" className="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-600 font-bold uppercase overflow-hidden text-xs hover:ring-2 hover:ring-teal-500 transition-all">
                        {auth.user.avatar_url ? (
                            <img src={auth.user.avatar_url} alt={auth.user.name} className="w-full h-full object-cover" />
                        ) : (
                            auth.user.name[0]
                        )}
                    </Link>
                </header>

                <div className="flex-1 p-6 lg:p-10 pb-28 lg:pb-10 overflow-y-auto">
                    <AnimatePresence mode="wait">
                        <motion.div
                            key={url}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                        >
                            {children}
                        </motion.div>
                    </AnimatePresence>
                </div>

                <MobileNav url={url} menuItems={menuItems} voiceProps={voiceProps} />
            </main>
        </div>
    );
}
