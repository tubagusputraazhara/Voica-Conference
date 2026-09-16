import React, { useState, useRef } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthLayout from '../Layouts/AuthLayout';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    ShieldCheckIcon as ShieldCheck, 
    BellIcon as Bell, 
    CameraIcon as Camera,
    CheckIcon as Save,
    CheckCircleIcon as CheckCircle2,
    MicrophoneIcon as Mic,
    ArrowRightOnRectangleIcon as LogOut
} from '@heroicons/react/24/solid';

import ProfileForm from '../Components/Settings/ProfileForm';
import PasswordForm from '../Components/Settings/PasswordForm';

export default function Settings({ user }) {
    const fileInputRef = useRef(null);
    const [avatarPreview, setAvatarPreview] = useState(user.avatar_url);

    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone,
        avatar_file: null,
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleAvatarClick = () => {
        fileInputRef.current.click();
    };

    const handleAvatarChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('avatar_file', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setAvatarPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        
        // Use post with _method: 'PUT' if needed, or just stay with POST for Laravel's handling of files
        post('/settings/update', {
            forceFormData: true,
            onSuccess: () => {
                reset('current_password', 'password', 'password_confirmation', 'avatar_file');
            },
        });
    };

    return (
        <AuthLayout>
            <Head title="Pengaturan Akun" />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <motion.div 
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="mb-12"
                >
                    <h1 className="text-5xl font-bold font-outfit mb-3 tracking-tighter text-slate-900">Pengaturan</h1>
                    <p className="text-slate-500 font-medium">Kelola profil, keamanan, dan preferensi akun Anda.</p>
                </motion.div>

                {/* Profile Snapshot (Hero Banner) */}
                <motion.div 
                    initial={{ opacity: 0, scale: 0.98 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ delay: 0.1 }}
                    className="bg-slate-900/90 backdrop-blur-3xl rounded-[48px] p-10 md:p-14 mb-12 text-white relative overflow-hidden border border-white/5 shadow-3xl"
                >
                    {/* Animated Decorative Element */}
                    <motion.div 
                        animate={{ 
                            scale: [1, 1.2, 1],
                            opacity: [0.1, 0.2, 0.1]
                        }}
                        transition={{ duration: 10, repeat: Infinity }}
                        className="absolute -top-20 -right-20 w-96 h-96 bg-teal-500/20 rounded-full blur-[100px]" 
                    />

                    <div className="relative z-10 flex flex-col md:flex-row items-center gap-10">
                        <motion.div 
                            whileHover={{ scale: 1.05 }}
                            className="relative group cursor-pointer" 
                            onClick={handleAvatarClick}
                        >
                            <div className="w-32 h-32 bg-white/10 rounded-full flex items-center justify-center text-white text-4xl font-bold border-4 border-white/10 shadow-2xl overflow-hidden relative">
                                {avatarPreview ? (
                                    <img src={avatarPreview} alt={user.name} className="w-full h-full object-cover" />
                                ) : (
                                    user.name[0]
                                )}
                                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <Camera className="w-7 h-7 text-white" />
                                </div>
                            </div>
                            <input 
                                type="file" 
                                ref={fileInputRef} 
                                onChange={handleAvatarChange} 
                                className="hidden" 
                                accept="image/*"
                            />
                        </motion.div>
                        <div className="text-center md:text-left">
                            <h3 className="text-4xl font-bold font-outfit mb-2 tracking-tight">{user.name}</h3>
                            <p className="text-slate-400 font-medium text-lg">{user.email || 'Email belum diatur'}</p>
                            <div className="flex flex-wrap justify-center md:justify-start gap-4 mt-6">
                                <span className="px-5 py-2 bg-white/5 border border-white/5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2">
                                    <ShieldCheck className="w-3.5 h-3.5 text-teal-400" />
                                    Account Verified
                                </span>
                                {user.voice_enrolled_at ? (
                                    <span className="px-5 py-2 bg-teal-500/20 text-teal-400 rounded-full text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2 border border-teal-500/20">
                                        <Mic className="w-3.5 h-3.5" />
                                        Voice ID: Active
                                    </span>
                                ) : (
                                    <span className="px-5 py-2 bg-rose-500/20 text-rose-400 rounded-full text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2 border border-rose-500/20">
                                        <Bell className="w-3.5 h-3.5" />
                                        Voice ID: Inactive
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </motion.div>

                <form onSubmit={submit}>
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
                        className="space-y-10"
                    >
                    {/* General Settings */}
                    <ProfileForm data={data} setData={setData} errors={errors} />

                    {/* Security Settings */}
                    <PasswordForm data={data} setData={setData} errors={errors} />

                    {/* Save Changes Floating Bar */}
                    <motion.div 
                        variants={{
                            hidden: { opacity: 0, y: 20 },
                            visible: { opacity: 1, y: 0 }
                        }}
                        className="flex items-center justify-between bg-white/40 backdrop-blur-xl p-8 rounded-[48px] border border-white/40 shadow-sm"
                    >
                        <div className="flex items-center gap-3">
                            <AnimatePresence>
                                {recentlySuccessful && (
                                    <motion.p 
                                        initial={{ opacity: 0, scale: 0.9, x: -10 }}
                                        animate={{ opacity: 1, scale: 1, x: 0 }}
                                        exit={{ opacity: 0, x: 10 }}
                                        className="text-teal-600 font-black text-[10px] uppercase tracking-[0.2em] flex items-center gap-2 bg-teal-500/10 px-6 py-3 rounded-full border border-teal-500/10 shadow-sm"
                                    >
                                        <CheckCircle2 className="w-4 h-4" />
                                        Data berhasil diperbarui
                                    </motion.p>
                                )}
                            </AnimatePresence>
                        </div>
                        <motion.button
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            type="submit"
                            disabled={processing}
                            className="h-16 px-12 bg-teal-600 text-white rounded-2xl font-black text-sm uppercase tracking-[0.2em] flex items-center justify-center gap-3 hover:bg-teal-700 transition-all shadow-2xl shadow-teal-600/40 disabled:opacity-50 group overflow-hidden relative"
                        >
                            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_2s_infinite]" />
                            <Save className="w-5 h-5 relative z-10" />
                            <span className="relative z-10">{processing ? 'Memproses...' : 'Simpan Perubahan'}</span>
                        </motion.button>
                    </motion.div>

                    {/* Manual Book Section */}
                    <motion.div 
                        variants={{
                            hidden: { opacity: 0, y: 20 },
                            visible: { opacity: 1, y: 0 }
                        }}
                        className="mt-12 bg-teal-500/10 border border-teal-500/20 rounded-[32px] p-8 text-center"
                    >
                        <h3 className="text-xl font-bold font-outfit mb-2 text-teal-800">📖 Buku Panduan VOICA</h3>
                        <p className="text-teal-600/80 mb-6 font-medium">Unduh buku panduan untuk mempelajari cara menggunakan aplikasi secara optimal.</p>
                        <a 
                            href="/Manual%20Book%20VOICA.pdf" 
                            target="_blank" 
                            download="Manual Book VOICA.pdf"
                            className="inline-flex h-16 px-12 bg-teal-600 text-white rounded-2xl font-black text-sm uppercase tracking-[0.2em] items-center justify-center gap-3 hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/30"
                        >
                            ⬇️ Download PDF
                        </a>
                    </motion.div>

                    {/* Logout Section */}
                    <motion.div 
                        variants={{
                            hidden: { opacity: 0, y: 20 },
                            visible: { opacity: 1, y: 0 }
                        }}
                        className="mt-12 mb-24 lg:hidden"
                    >
                        <Link 
                            href="/logout" 
                            method="post" 
                            as="button"
                            className="w-full h-20 bg-rose-500/10 border border-rose-500/20 text-rose-600 rounded-[32px] font-black text-sm uppercase tracking-[0.2em] flex items-center justify-center gap-4 hover:bg-rose-500/20 transition-all"
                        >
                            <LogOut className="w-6 h-6" />
                            Keluar dari Sesi
                        </Link>
                    </motion.div>
                    </motion.div>
                </form>
            </div>
        </AuthLayout>
    );
}
