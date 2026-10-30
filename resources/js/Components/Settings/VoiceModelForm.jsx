import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    MicrophoneIcon as Mic,
    CheckCircleIcon as CheckCircle2,
    SpeakerWaveIcon as Volume2,
} from '@heroicons/react/24/solid';
import SettingsSection from './SettingsSection';
import VoiceVisualizer from '../VoiceVisualizer';
import { useAudioRecorder } from '../../Hooks/useAudioRecorder';

const VOICE_MODELS = [
    { value: 'ecapa', label: 'ECAPA-TDNN', desc: 'Baseline — seimbang & sudah teruji' },
    { value: 'resnet34', label: 'ResNet-34', desc: 'Ringan & cepat' },
    { value: 'wavlm', label: 'WavLM', desc: 'Paling akurat, lebih berat' },
];

const MODEL_LABEL = Object.fromEntries(VOICE_MODELS.map(m => [m.value, m.label]));

export default function VoiceModelForm({ user }) {
    const [justUpdated, setJustUpdated] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        voice_model: user.voice_model || 'ecapa',
        voice_audio_base64: '',
        current_password: '',
    });

    const handleVoiceStop = (blob) => {
        const reader = new FileReader();
        reader.readAsDataURL(blob);
        reader.onloadend = () => {
            setData('voice_audio_base64', reader.result);
        };
    };

    const { isRecording, audioUrl, analyserRef, startRecording, stopRecording, clearAudio } = useAudioRecorder({ onStop: handleVoiceStop });

    const canSubmit = data.voice_audio_base64 && data.current_password.trim() !== '' && !processing;

    const submit = (e) => {
        e.preventDefault();
        post('/settings/voice-model', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('voice_audio_base64', 'current_password');
                clearAudio();
                setJustUpdated(true);
                setTimeout(() => setJustUpdated(false), 4000);
            },
        });
    };

    return (
        <SettingsSection
            title="Model Verifikasi Suara"
            description={`Saat ini: ${user.voice_model ? MODEL_LABEL[user.voice_model] ?? user.voice_model : 'Belum diatur'}`}
            icon={Mic}
        >
            <form onSubmit={submit} className="space-y-8">
                <p className="text-xs text-slate-500 leading-relaxed font-medium">
                    Ganti backbone AI yang dipakai untuk verifikasi suara Anda. Data akun (nama, saldo, transaksi) <strong>tidak akan hilang</strong> —
                    hanya cara sistem mengenali suara Anda yang diperbarui.
                </p>

                <div className="space-y-3">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Pilih Model Baru</label>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {VOICE_MODELS.map((m) => (
                            <button
                                key={m.value}
                                type="button"
                                onClick={() => setData('voice_model', m.value)}
                                disabled={processing}
                                className={`p-4 rounded-2xl border text-left transition-all ${
                                    data.voice_model === m.value
                                        ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/30'
                                        : 'border-slate-100 bg-white hover:border-slate-200'
                                }`}
                            >
                                <span className="flex items-center gap-2">
                                    <span className="block text-xs font-black text-slate-800 uppercase tracking-wide">{m.label}</span>
                                    {user.voice_model === m.value && (
                                        <span className="text-[9px] font-black text-teal-600 uppercase">Aktif</span>
                                    )}
                                </span>
                                <span className="block text-[10px] text-slate-400 mt-1 leading-tight">{m.desc}</span>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="bg-slate-900 border border-white/5 rounded-[32px] p-6 shadow-2xl">
                    <div className="flex items-center justify-between mb-4">
                        <span className="text-xs font-bold uppercase tracking-widest text-teal-300 flex items-center gap-2">
                            <span className={`w-2.5 h-2.5 rounded-full ${isRecording ? 'bg-rose-500 animate-pulse' : 'bg-teal-400'}`}></span>
                            {isRecording ? 'Merekam...' : 'Rekam Ulang Suara'}
                        </span>
                        {data.voice_audio_base64 && <CheckCircle2 className="w-5 h-5 text-teal-400" />}
                    </div>

                    <div className="h-20 flex items-center justify-center bg-black/30 rounded-2xl overflow-hidden mb-5 border border-white/5">
                        <VoiceVisualizer isActive={isRecording} color={isRecording ? '#f43f5e' : '#2dd4bf'} analyserRef={analyserRef} />
                    </div>

                    <div className="flex items-center justify-center gap-3">
                        {!isRecording ? (
                            <button
                                onClick={startRecording}
                                type="button"
                                className="px-6 py-3 bg-white/10 rounded-full text-sm font-bold transition-all border border-white/10 hover:bg-white/20 active:scale-95"
                            >
                                <span className="flex items-center gap-2 text-white">
                                    <Mic className="w-4 h-4 text-teal-400" />
                                    {data.voice_audio_base64 ? 'Rekam Ulang' : 'Mulai Perekaman'}
                                </span>
                            </button>
                        ) : (
                            <button
                                onClick={stopRecording}
                                type="button"
                                className="px-6 py-3 bg-rose-500 rounded-full text-sm font-bold text-white transition-all shadow-lg shadow-rose-500/20 hover:bg-rose-600 active:scale-95"
                            >
                                Berhenti & Simpan
                            </button>
                        )}
                        {audioUrl && !isRecording && (
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
                    {errors.voice_audio && <p className="text-rose-400 text-xs font-bold mt-3 text-center">{errors.voice_audio}</p>}
                </div>

                <div className="space-y-3">
                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Konfirmasi Kata Sandi</label>
                    <input
                        type="password"
                        value={data.current_password}
                        onChange={e => setData('current_password', e.target.value)}
                        className="w-full h-14 px-6 bg-white border-0 rounded-2xl shadow-inner focus:ring-2 focus:ring-teal-500 font-bold text-slate-900"
                        placeholder="Wajib diisi untuk konfirmasi identitas"
                    />
                    {errors.current_password && <p className="text-rose-500 text-[10px] font-black mt-2 ml-1 uppercase tracking-widest">{errors.current_password}</p>}
                </div>

                <div className="flex items-center justify-between">
                    <AnimatePresence>
                        {justUpdated && (
                            <motion.p
                                initial={{ opacity: 0, x: -10 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0 }}
                                className="text-teal-600 font-black text-[10px] uppercase tracking-[0.2em] flex items-center gap-2 bg-teal-500/10 px-5 py-3 rounded-full border border-teal-500/10"
                            >
                                <CheckCircle2 className="w-4 h-4" />
                                Model verifikasi suara diperbarui
                            </motion.p>
                        )}
                    </AnimatePresence>
                    <button
                        type="submit"
                        disabled={!canSubmit}
                        className="h-14 px-10 bg-teal-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-teal-700 transition-all shadow-xl shadow-teal-600/20 disabled:opacity-40 disabled:cursor-not-allowed ml-auto"
                    >
                        {processing ? 'Memproses...' : 'Ganti Model'}
                    </button>
                </div>
            </form>
        </SettingsSection>
    );
}
