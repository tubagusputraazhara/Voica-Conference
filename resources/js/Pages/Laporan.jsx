import React, { useState, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import AuthLayout from '../Layouts/AuthLayout';
import { 
    ArrowDownTrayIcon as Download, 
    FunnelIcon as Filter, 
    MagnifyingGlassIcon as Search, 
    ArrowUpCircleIcon as ArrowUpCircle, 
    ArrowDownCircleIcon as ArrowDownCircle, 
    WalletIcon as Wallet,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentTextIcon as FileText
} from '@heroicons/react/24/solid';
import TrendChart from '../Components/Laporan/TrendChart';
import CategoryChart from '../Components/Laporan/CategoryChart';

const ROWS_PER_PAGE = 20;

/**
 * SummaryCard — CSS hover, tidak pakai framer-motion (dirender 3x = masih ok tapi tetap hemat)
 */
const SummaryCard = ({ title, amount, icon: Icon, color }) => (
    <div className="relative group bg-white/40 backdrop-blur-xl p-7 rounded-[32px] border border-white/40 shadow-sm flex items-center gap-6 overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
        <div className={`absolute -right-4 -top-4 w-24 h-24 rounded-full blur-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 ${color.split(' ')[0]}`} />
        <Icon className="w-7 h-7 relative z-10" />
        <div className="relative z-10">
            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{title}</p>
            <h3 className="text-2xl font-bold font-outfit text-slate-900 tracking-tight">
                <span className="text-sm font-medium text-slate-400 mr-1">Rp</span>
                {new Intl.NumberFormat('id-ID').format(amount)}
            </h3>
        </div>
    </div>
);

export default function Laporan({ years }) {
    const today = new Date();
    const [filters, setFilters] = useState({
        bulan_dari: today.getMonth() + 1,
        tahun_dari: today.getFullYear(),
        bulan_sampai: today.getMonth() + 1,
        tahun_sampai: today.getFullYear(),
    });
    const [loading, setLoading] = useState(false);
    const [transactions, setTransactions] = useState([]);
    const [chartData, setChartData] = useState([]);
    const [categoryData, setCategoryData] = useState([]);
    const [summary, setSummary] = useState({
        total_pemasukan: 0,
        total_pengeluaran: 0,
        saldo_akhir: 0
    });

    // ─── Pagination state ──────────────────────────────────────────
    const [currentPage, setCurrentPage] = useState(1);

    const totalPages = Math.ceil(transactions.length / ROWS_PER_PAGE);
    const paginatedTransactions = transactions.slice(
        (currentPage - 1) * ROWS_PER_PAGE,
        currentPage * ROWS_PER_PAGE
    );

    const months = [
        { id: 1, name: 'Januari' }, { id: 2, name: 'Februari' }, { id: 3, name: 'Maret' },
        { id: 4, name: 'April' }, { id: 5, name: 'Mei' }, { id: 6, name: 'Juni' },
        { id: 7, name: 'Juli' }, { id: 8, name: 'Agustus' }, { id: 9, name: 'September' },
        { id: 10, name: 'Oktober' }, { id: 11, name: 'November' }, { id: 12, name: 'Desember' }
    ];

    const fetchLaporan = useCallback(async () => {
        setLoading(true);
        setCurrentPage(1); // Reset ke halaman 1 setiap fetch baru
        try {
            const response = await axios.get('/api/laporan/transactions', { params: filters });
            if (response.data.success) {
                setTransactions(response.data.transactions);
                setSummary(response.data.summary);
                setChartData(response.data.chart_data);
                setCategoryData(response.data.category_data);
            }
        } catch (err) {
            console.error('Failed to fetch reports:', err);
        } finally {
            setLoading(false);
        }
    }, [filters]);

    useEffect(() => {
        fetchLaporan();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleExport = () => {
        const queryParams = new URLSearchParams(filters).toString();
        window.location.href = `/api/laporan/export-pdf?${queryParams}`;
    };

    return (
        <AuthLayout>
            <Head title="Laporan Keuangan" />

            <div className="max-w-7xl mx-auto">
                {/* Header Area */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-8 mb-12">
                    <div>
                        <h1 className="text-5xl font-bold font-outfit mb-3 tracking-tighter text-slate-900">Analisa Laporan</h1>
                        <p className="text-slate-500 font-medium">Pantau kesehatan finansial Anda melalui data transaksional yang akurat.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-4">
                        <button 
                            onClick={handleExport}
                            className="bg-slate-900 text-white px-8 py-4 rounded-2xl flex items-center justify-center gap-3 font-bold hover:bg-slate-800 transition-all duration-200 shadow-2xl shadow-slate-900/40 active:scale-95"
                        >
                            <Download className="w-[18px] h-[18px]" />
                            <span>Unduh PDF</span>
                        </button>
                    </div>
                </div>

                {/* Filters Area */}
                <div className="bg-white/40 backdrop-blur-xl p-8 rounded-[48px] border border-white/40 shadow-sm mb-12">
                    <div className="flex items-center gap-3 mb-8">
                        <div className="w-10 h-10 bg-teal-500/10 rounded-xl flex items-center justify-center text-teal-600">
                            <Filter className="w-[18px] h-[18px]" />
                        </div>
                        <h4 className="font-black text-slate-900 uppercase tracking-[0.2em] text-[10px]">Filter Periode</h4>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 items-end">
                        <div className="space-y-3">
                            <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Dari Bulan</label>
                            <select 
                                value={filters.bulan_dari}
                                onChange={e => setFilters({...filters, bulan_dari: e.target.value})}
                                className="w-full h-14 px-5 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-sm"
                            >
                                {months.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-3">
                            <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tahun</label>
                            <select 
                                value={filters.tahun_dari}
                                onChange={e => setFilters({...filters, tahun_dari: e.target.value})}
                                className="w-full h-14 px-5 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-sm"
                            >
                                {years.map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>
                        <div className="hidden lg:flex items-center justify-center h-14 text-slate-300">
                            <ArrowUpCircle className="w-6 h-6 rotate-90 opacity-40" />
                        </div>
                        <div className="space-y-3">
                            <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sampai Bulan</label>
                            <select 
                                value={filters.bulan_sampai}
                                onChange={e => setFilters({...filters, bulan_sampai: e.target.value})}
                                className="w-full h-14 px-5 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-sm"
                            >
                                {months.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </select>
                        </div>
                        <div className="flex gap-3">
                            <div className="flex-1 space-y-3">
                                <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tahun</label>
                                <select 
                                    value={filters.tahun_sampai}
                                    onChange={e => setFilters({...filters, tahun_sampai: e.target.value})}
                                    className="w-full h-14 px-5 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-sm"
                                >
                                    {years.map(y => <option key={y} value={y}>{y}</option>)}
                                </select>
                            </div>
                            <button 
                                onClick={fetchLaporan}
                                disabled={loading}
                                className="h-14 w-14 bg-teal-600 text-white rounded-2xl flex items-center justify-center hover:bg-teal-700 transition-all duration-200 shadow-xl shadow-teal-600/30 disabled:opacity-50 active:scale-95 self-end"
                            >
                                <Search className="w-[22px] h-[22px]" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Chart Area */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-12">
                    <TrendChart chartData={chartData} />
                    <CategoryChart categoryData={categoryData} totalPengeluaran={summary.total_pengeluaran} />
                </div>

                {/* Summary Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <SummaryCard title="Pemasukan" amount={summary.total_pemasukan} icon={ArrowUpCircle} color="bg-teal-500/10 text-teal-600" />
                    <SummaryCard title="Pengeluaran" amount={summary.total_pengeluaran} icon={ArrowDownCircle} color="bg-rose-500/10 text-rose-600" />
                    <SummaryCard title="Saldo Akhir" amount={summary.saldo_akhir} icon={Wallet} color="bg-slate-900 text-white" />
                </div>

                {/* Transactions Table with Pagination */}
                <div className="bg-white/40 backdrop-blur-xl rounded-[48px] border border-white/40 shadow-sm overflow-hidden">
                    <div className="p-10 border-b border-white/40 flex items-center justify-between">
                        <div>
                            <h4 className="text-2xl font-bold font-outfit text-slate-900 tracking-tight">Financial History</h4>
                            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Daftar transaksi dalam periode</p>
                        </div>
                        <span className="text-[10px] font-black text-teal-600 uppercase tracking-[0.2em] bg-teal-500/10 px-4 py-2 rounded-full border border-teal-500/10">
                            {transactions.length} Entri Ditemukan
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-slate-900/5">
                                    <th className="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Tanggal</th>
                                    <th className="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Aktivitas</th>
                                    <th className="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kategori</th>
                                    <th className="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Nominal</th>
                                    <th className="px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? (
                                    <tr>
                                        <td colSpan="5" className="px-10 py-24 text-center">
                                            <div className="flex flex-col items-center gap-6">
                                                <div className="w-12 h-12 border-4 border-teal-600 border-t-transparent rounded-full animate-spin shadow-lg"></div>
                                                <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Memuat data laporan...</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : paginatedTransactions.length > 0 ? (
                                    // DOM hanya render ROWS_PER_PAGE (20) baris, bukan semua sekaligus
                                    paginatedTransactions.map((t) => (
                                        <tr 
                                            key={t.id}
                                            className="hover:bg-teal-50/30 transition-colors duration-150 group cursor-pointer"
                                        >
                                            <td className="px-10 py-6 text-sm font-bold text-slate-400">{t.tanggal}</td>
                                            <td className="px-10 py-6 text-sm font-bold text-slate-900 group-hover:text-teal-600 transition-colors tracking-tight">{t.keterangan}</td>
                                            <td className="px-10 py-6">
                                                <span className="px-4 py-1.5 bg-white shadow-sm border border-slate-100 rounded-full text-slate-600 font-black text-[9px] uppercase tracking-widest">
                                                    {t.kategori}
                                                </span>
                                            </td>
                                            <td className={`px-10 py-6 text-sm font-bold text-right font-outfit ${t.jenis === 'Pemasukan' ? 'text-teal-600' : 'text-slate-900'}`}>
                                                {t.jenis === 'Pemasukan' ? '+' : '-'}{t.jumlah_formatted}
                                            </td>
                                            <td className="px-10 py-6 text-sm font-bold text-slate-900 text-right font-outfit">{t.saldo_formatted}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="px-10 py-32 text-center text-slate-400">
                                            <div className="flex flex-col items-center gap-6">
                                                <FileText className="w-16 h-16 text-slate-100" />
                                                <p className="text-[10px] font-black uppercase tracking-[0.2em]">Tidak ada data untuk periode ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Controls */}
                    {totalPages > 1 && (
                        <div className="px-10 py-6 border-t border-white/40 flex items-center justify-between">
                            <p className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                Halaman {currentPage} dari {totalPages} ({transactions.length} entri)
                            </p>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                                    disabled={currentPage === 1}
                                    className="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-100 text-slate-600 hover:bg-teal-600 hover:text-white hover:border-teal-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm"
                                >
                                    <ChevronLeftIcon className="w-4 h-4" />
                                </button>

                                {/* Page numbers */}
                                {Array.from({ length: totalPages }, (_, i) => i + 1)
                                    .filter(p => p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1)
                                    .reduce((acc, p, idx, arr) => {
                                        if (idx > 0 && p - arr[idx - 1] > 1) acc.push('...');
                                        acc.push(p);
                                        return acc;
                                    }, [])
                                    .map((item, idx) => 
                                        item === '...' ? (
                                            <span key={`ellipsis-${idx}`} className="w-10 h-10 flex items-center justify-center text-slate-400 text-sm font-bold">...</span>
                                        ) : (
                                            <button
                                                key={item}
                                                onClick={() => setCurrentPage(item)}
                                                className={`w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-all duration-200 ${
                                                    currentPage === item 
                                                        ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/30' 
                                                        : 'bg-white border border-slate-100 text-slate-600 hover:bg-teal-50 hover:border-teal-200'
                                                }`}
                                            >
                                                {item}
                                            </button>
                                        )
                                    )
                                }

                                <button
                                    onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                                    disabled={currentPage === totalPages}
                                    className="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-100 text-slate-600 hover:bg-teal-600 hover:text-white hover:border-teal-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm"
                                >
                                    <ChevronRightIcon className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthLayout>
    );
}
