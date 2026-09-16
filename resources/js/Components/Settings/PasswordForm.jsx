import React from 'react';
import { motion } from 'framer-motion';
import { LockClosedIcon as Lock, MicrophoneIcon as Mic } from '@heroicons/react/24/solid';
import SettingsSection from './SettingsSection';

export default function PasswordForm({ data, setData, errors }) {
    return (
        <SettingsSection 
            title="Autentikasi & Keamanan" 
            description="Proteksi akun dengan enkripsi kata sandi."
            icon={Lock}
        >
            <div className="space-y-8">
                <motion.div 
                    whileHover={{ scale: 1.01 }}
                    className="bg-slate-900 border border-white/5 rounded-[32px] p-6 flex gap-6 items-start shadow-2xl"
                >
                    <div className="w-12 h-12 rounded-2xl bg-teal-500/10 flex items-center justify-center text-teal-400 flex-shrink-0 shadow-lg relative overflow-hidden group">
                        <div className="absolute inset-0 bg-teal-500/10 animate-pulse" />
                        <Mic className="w-6 h-6 relative z-10" />
                    </div>
                    <div>
                        <h5 className="text-sm font-black text-white uppercase tracking-widest mb-2">Voice-Lock Active</h5>
                        <p className="text-xs text-slate-400 leading-relaxed font-medium">
                            Perubahan pada keamanan akan memerlukan verifikasi suara tambahan jika Anda mengaktifkan perlindungan ketat dari dashboard.
                        </p>
                    </div>
                </motion.div>

                <div className="space-y-3">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Kata Sandi Saat Ini</label>
                    <input 
                        type="password"
                        value={data.current_password}
                        onChange={e => setData('current_password', e.target.value)}
                        className="w-full h-14 px-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                        placeholder="Wajib diisi untuk proses pembaruan"
                    />
                    {errors.current_password && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.current_password}</p>}
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div className="space-y-3">
                        <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Kata Sandi Baru</label>
                        <input 
                            type="password"
                            value={data.password}
                            onChange={e => setData('password', e.target.value)}
                            className="w-full h-14 px-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                            placeholder="Minimal 8 karakter"
                        />
                        {errors.password && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.password}</p>}
                    </div>
                    <div className="space-y-3">
                        <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Konfirmasi Sandi Baru</label>
                        <input 
                            type="password"
                            value={data.password_confirmation}
                            onChange={e => setData('password_confirmation', e.target.value)}
                            className="w-full h-14 px-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                            placeholder="Ulangi sandi baru"
                        />
                    </div>
                </div>
            </div>
        </SettingsSection>
    );
}
