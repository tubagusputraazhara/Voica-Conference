import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { MicrophoneIcon as Mic, PhoneIcon as Phone, LockClosedIcon as Lock, UserIcon, ArrowRightIcon as ArrowRight, XMarkIcon as X, CheckCircleIcon as CheckCircle2, ExclamationCircleIcon as AlertCircle, SpeakerWaveIcon as Volume2 } from '@heroicons/react/24/solid';
import VoiceVisualizer from '../Components/VoiceVisualizer';
import { useAudioRecorder } from '../Hooks/useAudioRecorder';

export default function Auth({ mode = 'login', status: propStatus }) {
    const { props } = usePage();
    const { flash, status: sessionStatus } = props;
    const status = propStatus || sessionStatus;
    
    const [authMode, setAuthMode] = useState(mode);
    
    useEffect(() => {
        setAuthMode(mode);
    }, [mode]);
    const [isVoiceLogin, setIsVoiceLogin] = useState(false);
    const [voiceStatus, setVoiceStatus] = useState('idle'); // idle, recording, processing, success, error
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        phone: '',
        password: '',
        password_confirmation: '',
        voice_audio_base64: '',
    });

    // Validasi Biodata Register (4 field wajib terisi & password cocok)
    const bioBioValid =
        authMode === 'register' &&
        data.name.trim() !== '' &&
        data.phone.trim() !== '' &&
        data.password.length >= 1 &&
        data.password_confirmation.length >= 1 &&
        data.password === data.password_confirmation;

    // Nomor telepon valid untuk voice-login
    const phoneValid = data.phone.trim() !== '';

    // Panel dinonaktifkan jika biodata belum lengkap (register) atau nomor telepon belum diisi (voice login)
    const panelDisabled =
        (authMode === 'register' && !bioBioValid) ||
        (authMode === 'login' && isVoiceLogin && !phoneValid);

    const handleVoiceStop = (blob) => {
        const reader = new FileReader();
        reader.readAsDataURL(blob);
        reader.onloadend = () => {
            setData('voice_audio_base64', reader.result);
            setVoiceStatus('ready');
        };
    };

    const { isRecording, audioUrl, analyserRef, startRecording, stopRecording, clearAudio } = useAudioRecorder({ onStop: handleVoiceStop });

    const toggleMode = () => {
        const newMode = authMode === 'login' ? 'register' : 'login';
        setAuthMode(newMode);
        setIsVoiceLogin(false);
        setVoiceStatus('idle');
        clearAudio();
        reset();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (isVoiceLogin && authMode === 'login') {
            post('/voice-login');
        } else {
            post(authMode === 'login' ? '/login' : '/register', {
                onSuccess: () => {
                    if (authMode === 'register') {
                        setAuthMode('login');
                        setIsVoiceLogin(false);
                    }
                }
            });
        }
    };

    const panelStatusLabel = () => {
        if (panelDisabled) {
            return authMode === 'register'
                ? 'Isi data diri dulu'
                : 'Isi nomor telepon dulu';
        }
        return isRecording ? 'Merekam frekuensi...' : 'Sistem Siap';
    };

    // Widget Perekam Suara Biometrik Utuh (Kotak Hijau)
    const renderVoiceWidget = (extraClass = '') => (
        <div className={`relative z-10 bg-teal-900 border border-teal-800/80 p-6 md:p-7 rounded-[28px] text-white shadow-xl shadow-teal-950/20 backdrop-blur-md transition-opacity duration-300 ${panelDisabled ? 'opacity-50' : 'opacity-100'} ${extraClass}`}>
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                    <div className={`w-2.5 h-2.5 rounded-full ${isRecording && !panelDisabled ? 'bg-rose-500 animate-pulse' : 'bg-teal-400'}`}></div>
                    <span className="text-xs font-bold uppercase tracking-widest text-teal-300">
                        {panelStatusLabel()}
                    </span>
                </div>
                {data.voice_audio_base64 && !panelDisabled && <CheckCircle2 className="w-5 h-5 text-teal-400" />}
            </div>
            
            <div className="h-24 flex items-center justify-center bg-black/30 rounded-2xl overflow-hidden mb-5 border border-white/5">
                <VoiceVisualizer isActive={isRecording && !panelDisabled} color={isRecording && !panelDisabled ? "#f43f5e" : "#2dd4bf"} analyserRef={analyserRef} />
            </div>

            <div className="flex items-center justify-center gap-3">
                {!isRecording ? (
                    <button 
                        onClick={!panelDisabled ? startRecording : undefined}
                        type="button"
                        disabled={panelDisabled}
                        className={`px-6 py-3 bg-white/10 rounded-full text-sm font-bold transition-all border border-white/10 group ${panelDisabled ? 'cursor-not-allowed' : 'hover:bg-white/20 active:scale-95'}`}
                    >
                        <span className="flex items-center gap-2">
                            <Mic className="w-4 h-4 text-teal-400 group-hover:scale-110 transition-transform" />
                            {data.voice_audio_base64 ? 'Rekam Ulang' : 'Mulai Perekaman'}
                        </span>
                    </button>
                ) : (
                    <button 
                        onClick={!panelDisabled ? stopRecording : undefined}
                        type="button"
                        disabled={panelDisabled}
                        className={`px-6 py-3 bg-rose-500 rounded-full text-sm font-bold transition-all shadow-lg shadow-rose-500/20 ${panelDisabled ? 'cursor-not-allowed opacity-50' : 'hover:bg-rose-600 active:scale-95'}`}
                    >
                        Berhenti & Simpan
                    </button>
                )}
                {audioUrl && !isRecording && !panelDisabled && (
                    <button
                        onClick={() => new Audio(audioUrl).play()}
                        type="button"
                        className="p-3 bg-teal-500/20 hover:bg-teal-500/30 rounded-full transition-all border border-teal-400/20 active:scale-95"
                        title="Dengarkan rekaman"
                    >
                        <Volume2 className="w-4 h-4 text-teal-400" />
                    </button>
                )}
            </div>
            
            <p className="mt-4 text-[11px] text-center text-white uppercase tracking-tighter font-bold">
                Ucapkan: "Voica buka kunci dompet saya hari ini"
            </p>

            {errors.voice_audio && (
                <p className="text-rose-400 text-xs font-bold mt-2 text-center">{errors.voice_audio}</p>
            )}
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6 font-inter">
            <Head title={authMode === 'login' ? 'Login' : 'Daftar Akun'} />

            <div className="w-full max-w-[1050px] bg-white rounded-[40px] shadow-2xl shadow-slate-200 overflow-hidden flex flex-col md:flex-row">
                {/* SISI KIRI (HIJAU TUA): 38% pada desktop, proporsional untuk branding & headline */}
                <div className="w-full md:w-[38%] bg-teal-900 p-8 md:p-10 lg:p-12 text-white relative flex flex-col justify-between overflow-hidden">
                    <div className="relative z-10">
                        <Link href="/" className="mb-6 md:mb-8 block">
                            <img src="/images/voica-logo.png" alt="Voica" className="h-24 md:h-20 lg:h-22 w-auto brightness-0 invert" />
                        </Link>

                        <h2 className="text-2xl lg:text-3xl font-bold font-outfit leading-tight mb-3">
                            {authMode === 'login' 
                                ? (isVoiceLogin ? 'Buka dengan Suara Anda.' : 'Selamat Datang Kembali.') 
                                : 'Pendaftaran Biometrik Suara.'}
                        </h2>
                        <p className="text-teal-100/70 text-xs lg:text-sm leading-relaxed">
                            Teknologi enkripsi suara unik untuk keamanan finansial tingkat tinggi.
                        </p>
                    </div>

                    {/* Fitur Keunggulan & Trust Highlights (Mengisi ruang tengah agar padat & elegan) */}
                    <div className="relative z-10 my-6 space-y-3.5">
                        <div className="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <div className="p-2 rounded-xl bg-teal-500/20 text-teal-400 shrink-0">
                                <Mic className="w-4 h-4" />
                            </div>
                            <div>
                                <h4 className="text-xs font-bold text-white uppercase tracking-wider">AI Voice Recognition</h4>
                                <p className="text-[11px] text-teal-100/60 mt-0.5 leading-snug">
                                    Ekstraksi frekuensi unik ECAPA-TDNN untuk autentikasi presisi tinggi.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <div className="p-2 rounded-xl bg-teal-500/20 text-teal-400 shrink-0">
                                <Lock className="w-4 h-4" />
                            </div>
                            <div>
                                <h4 className="text-xs font-bold text-white uppercase tracking-wider">Anti-Spoofing Protection</h4>
                                <p className="text-[11px] text-teal-100/60 mt-0.5 leading-snug">
                                    Mendeteksi keaslian suara langsung dan menolak sampel rekaman tiruan.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <div className="p-2 rounded-xl bg-teal-500/20 text-teal-400 shrink-0">
                                <CheckCircle2 className="w-4 h-4" />
                            </div>
                            <div>
                                <h4 className="text-xs font-bold text-white uppercase tracking-wider">Akses Dompet Kilat</h4>
                                <p className="text-[11px] text-teal-100/60 mt-0.5 leading-snug">
                                    Buka kunci saldo dan dompet finansial Anda dalam hitungan detik.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Footer Info Sisi Kiri */}
                    <div className="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between text-[11px] text-teal-200/60 font-semibold tracking-wider uppercase">
                        <span>Bank-Grade Security</span>
                        <span>VOICA FinTech</span>
                    </div>

                    {/* Decorative Background */}
                    <div className="absolute -bottom-20 -left-20 w-80 h-80 bg-teal-500/10 rounded-full blur-3xl"></div>
                </div>

                {/* SISI KANAN (FORM PUTIH): 62% pada desktop, ruang bernapas luas dan nyaman */}
                <div className="w-full md:w-[62%] p-8 md:p-12 lg:p-14 flex flex-col justify-center bg-white">
                    <motion.div
                        key={authMode + (isVoiceLogin ? 'voice' : 'pass')}
                        initial={{ opacity: 0, x: 20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <div className="mb-8">
                            <h3 className="text-3xl font-bold font-outfit mb-2 text-slate-900">
                                {authMode === 'login' ? 'Masuk ke Akun' : 'Daftar Baru'}
                            </h3>
                            <p className="text-slate-400 font-medium text-sm">
                                {authMode === 'login' 
                                    ? 'Pilih metode masuk yang anda inginkan.' 
                                    : 'Isi data diri untuk memulai verifikasi suara.'}
                            </p>
                        </div>

                        {/* Flash Messages */}
                        <AnimatePresence>
                            {(status || flash?.success || flash?.error || errors.error) && (
                                <motion.div
                                    initial={{ opacity: 0, height: 0, marginBottom: 0 }}
                                    animate={{ opacity: 1, height: 'auto', marginBottom: 24 }}
                                    exit={{ opacity: 0, height: 0, marginBottom: 0 }}
                                    className={`p-4 rounded-2xl flex items-start gap-3 border ${
                                        (flash?.error || errors.error) ? 'bg-rose-50 border-rose-100 text-rose-700' : 'bg-teal-50 border-teal-100 text-teal-700'
                                    }`}
                                >
                                    {(flash?.error || errors.error) ? <AlertCircle className="w-[18px] h-[18px] shrink-0" /> : <CheckCircle2 className="w-[18px] h-[18px] shrink-0" />}
                                    <span className="text-sm font-bold leading-tight">
                                        {status || flash?.success || flash?.error || errors.error}
                                    </span>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Mode Toggle (Hanya untuk Login) */}
                        {authMode === 'login' && (
                            <div className="flex p-1 bg-slate-50 rounded-2xl mb-8 border border-slate-100">
                                <button 
                                    onClick={() => setIsVoiceLogin(false)}
                                    className={`flex-1 py-3 rounded-xl text-sm font-bold transition-all ${!isVoiceLogin ? 'bg-white shadow-sm text-teal-600' : 'text-slate-400 hover:text-slate-600'}`}
                                >
                                    Kata Sandi
                                </button>
                                <button 
                                    onClick={() => setIsVoiceLogin(true)}
                                    className={`flex-1 py-3 rounded-xl text-sm font-bold transition-all ${isVoiceLogin ? 'bg-white shadow-sm text-teal-600' : 'text-slate-400 hover:text-slate-600'}`}
                                >
                                    Suara (Biometrik)
                                </button>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {/* Input Nama Lengkap (Register saja) */}
                            {authMode === 'register' && (
                                <div className="space-y-2">
                                    <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nama Lengkap</label>
                                    <div className="relative">
                                        <UserIcon className="w-[18px] h-[18px] absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input 
                                            type="text"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            className="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold"
                                            placeholder="Nama anda"
                                            disabled={processing}
                                        />
                                    </div>
                                    {errors.name && <p className="text-rose-500 text-[10px] font-bold mt-1 ml-1">{errors.name}</p>}
                                </div>
                            )}

                            {/* Input Nomor Telepon */}
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nomor Telepon</label>
                                <div className="relative">
                                    <Phone className="w-[18px] h-[18px] absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input 
                                        type="tel"
                                        value={data.phone}
                                        onChange={e => setData('phone', e.target.value)}
                                        className="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold"
                                        placeholder="0812..."
                                        disabled={processing}
                                    />
                                </div>
                                {errors.phone && <p className="text-rose-500 text-[10px] font-bold mt-1 ml-1">{errors.phone}</p>}
                            </div>

                            {/* Input Kata Sandi (Untuk Login Tab Kata Sandi & Register) */}
                            {!isVoiceLogin && (
                                <div className="space-y-2">
                                    <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Kata Sandi</label>
                                    <div className="relative">
                                        <Lock className="w-[18px] h-[18px] absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input 
                                            type="password"
                                            value={data.password}
                                            onChange={e => setData('password', e.target.value)}
                                            className="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold"
                                            placeholder="••••••••"
                                            disabled={processing}
                                        />
                                    </div>
                                    {errors.password && <p className="text-rose-500 text-[10px] font-bold mt-1 ml-1">{errors.password}</p>}
                                </div>
                            )}

                            {/* Input Konfirmasi Kata Sandi (Register saja) */}
                            {authMode === 'register' && (
                                <div className="space-y-2">
                                    <label className="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Konfirmasi Kata Sandi</label>
                                    <div className="relative">
                                        <Lock className="w-[18px] h-[18px] absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input 
                                            type="password"
                                            value={data.password_confirmation}
                                            onChange={e => setData('password_confirmation', e.target.value)}
                                            className="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold"
                                            placeholder="••••••••"
                                            disabled={processing}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* 1. POSISI WIDGET SUARA DI REGISTER: Setelah Konfirmasi Kata Sandi, Sebelum Tombol Daftar Akun */}
                            {authMode === 'register' && (
                                <div className="pt-2">
                                    {renderVoiceWidget()}
                                </div>
                            )}

                            {/* 2. POSISI WIDGET SUARA DI LOGIN TAB SUARA (BIOMETRIK): Setelah Nomor Telepon, Sebelum Tombol Masuk Sekarang */}
                            {authMode === 'login' && isVoiceLogin && (
                                <div className="pt-2">
                                    {renderVoiceWidget()}
                                </div>
                            )}

                            {/* Tombol Submit */}
                            <button
                                type="submit"
                                disabled={
                                    processing ||
                                    (isVoiceLogin && !data.voice_audio_base64) ||
                                    (authMode === 'register' && (!bioBioValid || !data.voice_audio_base64))
                                }
                                className="w-full h-14 !mt-6 bg-teal-600 text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Memproses...' : (authMode === 'login' ? 'Masuk Sekarang' : 'Daftar Akun')}
                                <ArrowRight className="w-5 h-5" />
                            </button>
                        </form>

                        <div className="mt-8 text-center">
                            <button onClick={toggleMode} className="text-sm font-bold text-slate-400 hover:text-teal-600 transition-colors">
                                {authMode === 'login' 
                                    ? "Belum punya akun? Daftar gratis" 
                                    : "Sudah punya akun? Login di sini"}
                            </button>
                        </div>
                    </motion.div>
                </div>
            </div>
        </div>
    );
}
