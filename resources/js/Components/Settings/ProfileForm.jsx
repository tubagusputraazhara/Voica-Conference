import React from 'react';
import { UserIcon as User, EnvelopeIcon as Mail, PhoneIcon as Phone } from '@heroicons/react/24/solid';
import SettingsSection from './SettingsSection';

export default function ProfileForm({ data, setData, errors }) {
    return (
        <SettingsSection 
            title="Informasi Profil" 
            description="Perbarui informasi identitas publik Anda."
            icon={User}
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-3 lg:col-span-2">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Lengkap</label>
                    <div className="relative">
                        <User className="absolute w-5 h-5 left-5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            type="text"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            className="w-full h-14 pl-14 pr-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                            placeholder="Masukkan nama lengkap"
                            required
                        />
                    </div>
                    {errors.name && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.name}</p>}
                </div>
                <div className="space-y-3">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Alamat Email</label>
                    <div className="relative">
                        <Mail className="absolute w-5 h-5 left-5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            type="email"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                            className="w-full h-14 pl-14 pr-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                            placeholder="example@email.com"
                        />
                    </div>
                    {errors.email && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.email}</p>}
                </div>
                <div className="space-y-3">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Telepon</label>
                    <div className="relative">
                        <Phone className="absolute w-5 h-5 left-5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            type="tel"
                            value={data.phone}
                            onChange={e => setData('phone', e.target.value)}
                            className="w-full h-14 pl-14 pr-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                            placeholder="08xxxxxxxxxx"
                            required
                        />
                    </div>
                    {errors.phone && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.phone}</p>}
                </div>
            </div>
        </SettingsSection>
    );
}
