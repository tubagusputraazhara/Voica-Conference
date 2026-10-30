import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    MicrophoneIcon as Mic, 
    CheckCircleIcon as CheckCircle2, 
    ArrowRightIcon as ArrowRight, 
    ShieldCheckIcon as ShieldCheck,
    Bars3Icon as MenuIcon,
    XMarkIcon as CloseIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    BellAlertIcon,
    ClockIcon,
    SparklesIcon,
    LockClosedIcon,
    BoltIcon
} from '@heroicons/react/24/solid';

export default function Welcome() {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [navHidden, setNavHidden] = useState(false);

    // Auto-hide navbar: sembunyi saat scroll ke bawah, muncul lagi saat scroll ke atas
    useEffect(() => {
        let lastY = window.scrollY;
        const onScroll = () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) < 8) return;
            setNavHidden(y > lastY && y > 80);
            lastY = y;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <div className="min-h-screen bg-slate-50 font-inter text-slate-900 selection:bg-teal-100 selection:text-teal-900 scroll-smooth">
            <Head title="VOICA - Catat Keuangan Cukup dengan Bicara" />

            {/* 1. NAVBAR SEDERHANA & STICKY */}
            <nav className={`sticky top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 transition-transform duration-300 ${navHidden && !mobileMenuOpen ? '-translate-y-full' : 'translate-y-0'}`}>
                <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between gap-3">
                    {/* Tombol Menu Mobile (kiri) */}
                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="md:hidden -ml-2 p-2 text-slate-600 hover:text-slate-900 rounded-lg"
                        aria-label="Menu"
                    >
                        {mobileMenuOpen ? <CloseIcon className="w-6 h-6" /> : <MenuIcon className="w-6 h-6" />}
                    </button>

                    {/* Logo: kiri di desktop, kanan di mobile */}
                    <Link href="/" className="flex items-center ml-auto md:ml-0">
                        <img src="/images/voica-logo-wordmark.png" alt="VOICA" className="h-7 md:h-11 w-auto" />
                    </Link>

                    {/* Nav Desktop Tengah/Kanan */}
                    <div className="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                        <a href="#cara-kerja" className="hover:text-teal-600 transition-colors">Cara Kerja</a>
                        <a href="#keamanan" className="hover:text-teal-600 transition-colors">Keamanan</a>
                        <a href="#manfaat" className="hover:text-teal-600 transition-colors">Manfaat</a>
                    </div>

                    {/* CTA Kanan Desktop */}
                    <div className="hidden md:flex items-center gap-4">
                        <Link 
                            href="/login" 
                            className="px-4 py-2 text-sm font-bold text-slate-700 hover:text-teal-600 transition-colors"
                        >
                            Masuk
                        </Link>
                        <Link 
                            href="/register" 
                            className="px-5 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-bold shadow-md shadow-teal-600/20 hover:bg-teal-700 transition-all"
                        >
                            Daftar Gratis
                        </Link>
                    </div>

                    {/* Masuk Mobile (kanan) */}
                    <Link
                        href="/login"
                        className="md:hidden h-8 inline-flex items-center px-3.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors"
                    >
                        Masuk
                    </Link>
                </div>

                {/* Mobile Drawer */}
                <AnimatePresence>
                    {mobileMenuOpen && (
                        <motion.div 
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                            className="md:hidden bg-white border-b border-slate-200 px-6 py-5 space-y-4"
                        >
                            <div className="flex flex-col space-y-3 font-semibold text-slate-600">
                                <a 
                                    href="#cara-kerja" 
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-1 hover:text-teal-600"
                                >
                                    Cara Kerja
                                </a>
                                <a 
                                    href="#keamanan" 
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-1 hover:text-teal-600"
                                >
                                    Keamanan
                                </a>
                                <a 
                                    href="#manfaat" 
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-1 hover:text-teal-600"
                                >
                                    Manfaat
                                </a>
                            </div>
                            <div className="pt-3 border-t border-slate-100 flex flex-col gap-2">
                                <Link 
                                    href="/register" 
                                    className="w-full py-3 bg-teal-600 text-white rounded-xl text-center text-sm font-bold shadow-md shadow-teal-600/20"
                                >
                                    Daftar Gratis
                                </Link>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </nav>

            {/* 2. HERO - CERITA UTAMA */}
            <section className="relative pt-12 md:pt-20 pb-20 md:pb-28 overflow-hidden">
                <div className="max-w-7xl mx-auto px-6 grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    {/* Teks Hero */}
                    <div className="lg:col-span-7 space-y-6 md:space-y-8 text-center lg:text-left">
                        {/* Eyebrow */}
                        <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal-50 border border-teal-200/80 text-teal-800 text-xs font-bold uppercase tracking-wider">
                            <span className="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                            Pencatatan keuangan berbasis suara
                        </div>

                        {/* Headline */}
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold font-outfit text-slate-900 leading-[1.15] tracking-tight">
                            Catat keuangan cukup dengan <span className="text-teal-600 underline decoration-teal-300 decoration-wavy decoration-2">bicara</span>.
                        </h1>

                        {/* Subheadline */}
                        <p className="text-lg sm:text-xl text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0 font-normal">
                            VOICA membantu Anda mencatat pemasukan dan pengeluaran secara cepat, tanpa repot mengetik — agar Anda bisa tetap fokus menjalankan usaha.
                        </p>

                        {/* CTA Buttons */}
                        <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                            <Link 
                                href="/register" 
                                className="w-full sm:w-auto px-8 py-4 bg-teal-600 text-white rounded-2xl text-base font-bold shadow-xl shadow-teal-600/25 hover:bg-teal-700 transition-all flex items-center justify-center gap-2 group"
                            >
                                Mulai Gratis
                                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <Link 
                                href="/login" 
                                className="w-full sm:w-auto px-6 py-4 bg-white hover:bg-slate-100 text-slate-700 rounded-2xl text-base font-bold border border-slate-200 transition-all text-center"
                            >
                                Saya sudah punya akun
                            </Link>
                        </div>
                    </div>

                    {/* Visual Hero: Komposisi UI Mikrofon -> Waveform -> Kartu Transaksi */}
                    <div className="lg:col-span-5 relative">
                        <div className="relative mx-auto max-w-md bg-white rounded-3xl p-6 shadow-2xl shadow-slate-200 border border-slate-100">
                            {/* Header Kartu Mockup */}
                            <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-2xl bg-teal-900 flex items-center justify-center text-teal-400 shadow-md">
                                        <Mic className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-bold text-slate-900 uppercase tracking-wider">Input Suara Langsung</div>
                                        <div className="text-[11px] text-teal-600 font-semibold flex items-center gap-1">
                                            <span className="w-1.5 h-1.5 rounded-full bg-teal-500 animate-ping"></span>
                                            Mendengarkan ucapan Anda...
                                        </div>
                                    </div>
                                </div>
                                <span className="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">
                                    Real-Time
                                </span>
                            </div>

                            {/* Waveform Box */}
                            <div className="bg-teal-950 rounded-2xl p-4 text-white mb-5 shadow-inner">
                                <div className="flex items-center justify-between text-[11px] text-teal-300 font-mono mb-2">
                                    <span>FREKUENSI AUDIO</span>
                                    <span>44.1 kHz</span>
                                </div>
                                {/* Waveform Bars Ilustrasi */}
                                <div className="h-12 flex items-center justify-center gap-1 px-2">
                                    {[20, 35, 60, 45, 80, 100, 75, 90, 50, 65, 85, 40, 70, 95, 60, 35, 50, 30].map((h, idx) => (
                                        <motion.div
                                            key={idx}
                                            animate={{ height: [`${h * 0.4}%`, `${h}%`, `${h * 0.3}%`] }}
                                            transition={{ repeat: Infinity, duration: 1.2 + (idx % 3) * 0.2, ease: "easeInOut" }}
                                            className="w-1.5 bg-gradient-to-t from-teal-500 to-teal-200 rounded-full"
                                            style={{ minHeight: '6px' }}
                                        />
                                    ))}
                                </div>
                                <div className="mt-3 text-center text-xs font-medium text-teal-100/90 italic bg-white/5 py-1.5 px-3 rounded-lg">
                                    "Jual kopi tiga puluh ribu"
                                </div>
                            </div>

                            {/* Hasil Ekstraksi Otomatis */}
                            <div className="space-y-3">
                                <div className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Hasil Deteksi Otomatis
                                </div>
                                <div className="p-4 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center">
                                            <CheckCircle2 className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div className="text-xs font-bold text-slate-800">Pemasukan tercatat</div>
                                            <div className="text-[11px] text-slate-500">Kategori: Penjualan Produk</div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-base font-extrabold text-teal-700">+Rp30.000</div>
                                        <div className="text-[10px] text-teal-600 font-bold">Baru saja</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Subtle Glow Backdrop */}
                        <div className="absolute -top-10 -right-10 w-72 h-72 bg-teal-400/10 rounded-full blur-3xl -z-10"></div>
                        <div className="absolute -bottom-10 -left-10 w-72 h-72 bg-emerald-400/10 rounded-full blur-3xl -z-10"></div>
                    </div>
                </div>
            </section>

            {/* 3. MASALAH YANG DISELESAIKAN */}
            <section className="py-20 bg-white border-y border-slate-200/60">
                <div className="max-w-7xl mx-auto px-6">
                    <div className="max-w-3xl mx-auto text-center mb-16 space-y-4">
                        <h2 className="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 tracking-tight">
                            Saat tangan sibuk, pencatatan tetap jalan.
                        </h2>
                        <p className="text-base sm:text-lg text-slate-600 leading-relaxed font-normal">
                            Banyak pelaku usaha mencampur uang pribadi dan usaha atau melewatkan pencatatan karena proses manual terasa lambat. VOICA hadir agar transaksi dapat dicatat saat aktivitas tetap berlangsung.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Kartu 1 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-teal-200 transition-all">
                            <div className="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center mb-6">
                                <Mic className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Tak perlu mengetik</h3>
                            <p className="text-slate-600 text-sm leading-relaxed">
                                Cukup sampaikan transaksi secara natural tanpa repot membuka kalkulator atau mengetik angka satu per satu.
                            </p>
                        </div>

                        {/* Kartu 2 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-teal-200 transition-all">
                            <div className="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center mb-6">
                                <ClockIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Tak ada transaksi yang terlewat</h3>
                            <p className="text-slate-600 text-sm leading-relaxed">
                                Catat saat itu juga dalam beberapa detik tepat setelah melayani pembeli atau membeli perlengkapan usaha.
                            </p>
                        </div>

                        {/* Kartu 3 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-teal-200 transition-all">
                            <div className="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center mb-6">
                                <ChartBarIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Lebih mudah melihat arah keuangan</h3>
                            <p className="text-slate-600 text-sm leading-relaxed">
                                Pemasukan, pengeluaran, dan ringkasan tersusun lebih rapi sehingga Anda tahu persis ke mana arus uang Anda bergerak.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* 4. CARA KERJA - TIGA LANGKAH */}
            <section id="cara-kerja" className="py-24 bg-slate-50 scroll-mt-16">
                <div className="max-w-7xl mx-auto px-6">
                    <div className="text-center max-w-2xl mx-auto mb-16 space-y-3">
                        {/* Eyebrow */}
                        <div className="text-xs font-bold text-teal-600 uppercase tracking-widest">
                            Cara kerja VOICA
                        </div>
                        {/* Judul */}
                        <h2 className="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 tracking-tight">
                            Cukup tiga langkah untuk mencatat transaksi.
                        </h2>
                        {/* Pengantar */}
                        <p className="text-base text-slate-600 leading-relaxed font-normal max-w-xl mx-auto">
                            Tidak perlu membuka banyak menu atau mengetik angka. Sampaikan transaksi Anda, lalu biarkan VOICA membantu mencatatnya.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-3 gap-8 relative">
                        {/* Langkah 1 */}
                        <div className="relative bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm flex flex-col justify-between">
                            <div>
                                <div className="text-5xl font-black text-teal-100 font-outfit mb-4">01</div>
                                <div className="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-5">
                                    <Mic className="w-6 h-6" />
                                </div>
                                <h3 className="text-2xl font-bold font-outfit text-slate-900 mb-2">Bicara</h3>
                                <p className="text-slate-600 text-sm leading-relaxed">
                                    Tekan tombol mikrofon, lalu sebutkan transaksi Anda secara natural. Contoh: <span className="font-semibold text-slate-800">"Beli bahan baku lima belas ribu."</span>
                                </p>
                            </div>
                        </div>

                        {/* Langkah 2 */}
                        <div className="relative bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm flex flex-col justify-between">
                            <div>
                                <div className="text-5xl font-black text-teal-100 font-outfit mb-4">02</div>
                                <div className="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-5">
                                    <SparklesIcon className="w-6 h-6" />
                                </div>
                                <h3 className="text-2xl font-bold font-outfit text-slate-900 mb-2">VOICA Memahami</h3>
                                <p className="text-slate-600 text-sm leading-relaxed">
                                    Sistem membantu mengenali nominal dan jenis transaksi dari ucapan Anda, lalu menyiapkannya sebagai catatan.
                                </p>
                            </div>
                        </div>

                        {/* Langkah 3 */}
                        <div className="relative bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm flex flex-col justify-between">
                            <div>
                                <div className="text-5xl font-black text-teal-100 font-outfit mb-4">03</div>
                                <div className="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-5">
                                    <CheckCircle2 className="w-6 h-6" />
                                </div>
                                <h3 className="text-2xl font-bold font-outfit text-slate-900 mb-2">Tercatat Rapi</h3>
                                <p className="text-slate-600 text-sm leading-relaxed">
                                    Transaksi tersimpan dalam hitungan detik dan siap dilihat di ringkasan keuangan Anda.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 5. MANFAAT DAN HASIL */}
            <section id="manfaat" className="py-24 bg-white border-y border-slate-200/60 scroll-mt-16">
                <div className="max-w-7xl mx-auto px-6">
                    <div className="text-center max-w-2xl mx-auto mb-16 space-y-3">
                        <h2 className="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 tracking-tight">
                            Bukan hanya mencatat. Bantu Anda melihat gambaran besarnya.
                        </h2>
                    </div>

                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Manfaat 1 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center mb-6 shadow-md shadow-teal-600/20">
                                <ChartBarIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Ringkasan keuangan</h3>
                            <p className="text-slate-600 text-sm leading-relaxed mb-6">
                                Lihat pemasukan, pengeluaran, dan saldo secara cepat.
                            </p>
                            {/* Visual Mini Mockup */}
                            <div className="p-4 bg-white rounded-2xl border border-slate-200/70 space-y-2 text-xs">
                                <div className="flex justify-between text-slate-500">
                                    <span>Saldo Bulan Ini</span>
                                    <span className="font-bold text-teal-600">Surplus</span>
                                </div>
                                <div className="text-lg font-black text-slate-900">Rp4.850.000</div>
                            </div>
                        </div>

                        {/* Manfaat 2 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center mb-6 shadow-md shadow-teal-600/20">
                                <ArrowTrendingUpIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Tren keuangan</h3>
                            <p className="text-slate-600 text-sm leading-relaxed mb-6">
                                Pahami pola transaksi tanpa harus membaca angka yang rumit.
                            </p>
                            {/* Visual Mini Mockup */}
                            <div className="p-4 bg-white rounded-2xl border border-slate-200/70 space-y-2 text-xs">
                                <div className="flex justify-between text-slate-500">
                                    <span>Arus Transaksi</span>
                                    <span className="font-bold text-emerald-600">+18% Stabil</span>
                                </div>
                                <div className="h-4 bg-slate-100 rounded-full overflow-hidden flex">
                                    <div className="bg-teal-500 w-3/4"></div>
                                    <div className="bg-amber-400 w-1/4"></div>
                                </div>
                            </div>
                        </div>

                        {/* Manfaat 3 */}
                        <div className="p-8 rounded-3xl bg-slate-50 border border-slate-100">
                            <div className="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center mb-6 shadow-md shadow-teal-600/20">
                                <BellAlertIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-slate-900 mb-3">Peringatan anggaran</h3>
                            <p className="text-slate-600 text-sm leading-relaxed mb-6">
                                Dapatkan pengingat ketika pengeluaran mendekati batas yang ditentukan.
                            </p>
                            {/* Visual Mini Mockup */}
                            <div className="p-4 bg-white rounded-2xl border border-slate-200/70 space-y-2 text-xs">
                                <div className="flex justify-between text-slate-500">
                                    <span>Batas Belanja</span>
                                    <span className="font-bold text-amber-600">Mendekati Limit</span>
                                </div>
                                <div className="text-sm font-bold text-slate-800">78% Terpakai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 6. KEAMANAN SUARA */}
            <section id="keamanan" className="py-24 bg-teal-900 text-white relative overflow-hidden scroll-mt-16">
                <div className="max-w-7xl mx-auto px-6 relative z-10">
                    <div className="max-w-3xl mx-auto text-center mb-16 space-y-4">
                        <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold font-outfit tracking-tight">
                            Suara Anda membantu menjaga akses tetap milik Anda.
                        </h2>
                        <p className="text-base sm:text-lg text-teal-100/75 leading-relaxed font-normal">
                            VOICA menggunakan verifikasi suara dan pemeriksaan liveness untuk membantu memastikan akses berasal dari pengguna yang sah, bukan sekadar rekaman suara.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Indikator 1 */}
                        <div className="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md">
                            <div className="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-6">
                                <Mic className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-white mb-2">Verifikasi identitas suara</h3>
                            <p className="text-teal-100/70 text-sm leading-relaxed">
                                Mengenali pola vokal pemilik akun.
                            </p>
                        </div>

                        {/* Indikator 2 */}
                        <div className="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md">
                            <div className="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-6">
                                <BoltIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-white mb-2">Deteksi suara langsung</h3>
                            <p className="text-teal-100/70 text-sm leading-relaxed">
                                Membantu mengenali upaya replay atau pemalsuan suara.
                            </p>
                        </div>

                        {/* Indikator 3 */}
                        <div className="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md">
                            <div className="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-6">
                                <LockClosedIcon className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-bold font-outfit text-white mb-2">Akses ditolak bila tidak memenuhi ambang keamanan</h3>
                            <p className="text-teal-100/70 text-sm leading-relaxed">
                                Menerapkan prinsip default-deny demi menjaga integritas akun Anda.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Decorative Elements */}
                <div className="absolute -bottom-24 -left-24 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl"></div>
                <div className="absolute -top-24 -right-24 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
            </section>

            {/* 7. CTA PENUTUP */}
            <section className="py-24 bg-slate-50">
                <div className="max-w-4xl mx-auto px-6 text-center space-y-8">
                    <h2 className="text-3xl sm:text-5xl font-extrabold font-outfit text-slate-900 tracking-tight">
                        Mulai mencatat dengan cara yang lebih mudah.
                    </h2>
                    <p className="text-lg text-slate-600 max-w-xl mx-auto font-normal">
                        Bicara, catat, dan pahami keuangan Anda bersama VOICA.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <Link 
                            href="/register" 
                            className="w-full sm:w-auto px-10 py-4 bg-teal-600 text-white rounded-2xl text-lg font-bold shadow-xl shadow-teal-600/25 hover:bg-teal-700 transition-all flex items-center justify-center gap-2 group"
                        >
                            Buat Akun Gratis
                            <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>
                    <div>
                        <Link 
                            href="/login" 
                            className="text-sm font-semibold text-slate-500 hover:text-teal-600 transition-colors"
                        >
                            Sudah punya akun? <span className="underline font-bold">Masuk</span>
                        </Link>
                    </div>
                </div>
            </section>

            {/* 8. FOOTER */}
            <footer className="py-14 bg-slate-900 text-white border-t border-slate-800">
                <div className="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-8">
                    {/* Logo & Slogan */}
                    <div className="flex flex-col items-center md:items-start text-center md:text-left gap-2">
                        <img src="/images/voica-logo-wordmark.png" alt="VOICA" className="h-9 w-auto brightness-0 invert" />
                        <p className="text-sm text-slate-400 mt-1 max-w-sm">
                            Pencatatan keuangan yang lebih mudah melalui suara.
                        </p>
                    </div>

                    {/* Anchor Links */}
                    <div className="flex items-center gap-8 text-sm font-semibold text-slate-400">
                        <a href="#cara-kerja" className="hover:text-teal-400 transition-colors">Cara Kerja</a>
                        <a href="#keamanan" className="hover:text-teal-400 transition-colors">Keamanan</a>
                        <a href="#manfaat" className="hover:text-teal-400 transition-colors">Manfaat</a>
                    </div>

                    {/* Copyright */}
                    <div className="text-xs text-slate-500 font-medium">
                        © 2026 VOICA. Hak cipta dilindungi.
                    </div>
                </div>
            </footer>
        </div>
    );
}
