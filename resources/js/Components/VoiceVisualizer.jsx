import React, { useEffect, useRef } from 'react';

const VoiceVisualizer = ({ isActive = false, color = '#2A8576', analyserRef = null }) => {
    const canvasRef = useRef(null);
    const requestRef = useRef();

    const animate = (time) => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;

        ctx.clearRect(0, 0, width, height);

        const analyser = analyserRef?.current;

        if (isActive && analyser) {
            // REAL-TIME: Draw actual microphone waveform
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            analyser.getByteTimeDomainData(dataArray);

            ctx.beginPath();
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            const sliceWidth = width / bufferLength;
            let x = 0;

            for (let i = 0; i < bufferLength; i++) {
                const v = dataArray[i] / 128.0; // Normalize to 0-2 range
                const y = (v * height) / 2;

                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);

                x += sliceWidth;
            }

            ctx.stroke();

            // Draw a subtle glow behind the waveform
            ctx.globalAlpha = 0.15;
            ctx.strokeStyle = color;
            ctx.lineWidth = 8;
            ctx.stroke();
            ctx.globalAlpha = 1;
        } else {
            // IDLE: Slow ambient sine wave
            ctx.beginPath();
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.globalAlpha = 0.4;

            const points = 60;
            const gap = width / points;

            for (let i = 0; i < points; i++) {
                const x = i * gap;
                const amplitude = 4 + Math.sin(time / 800 + i * 0.3) * 3;
                const y = height / 2 + Math.sin(time / 400 + i * 0.15) * amplitude;

                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }

            ctx.stroke();
            ctx.globalAlpha = 1;
        }

        requestRef.current = requestAnimationFrame(animate);
    };

    useEffect(() => {
        requestRef.current = requestAnimationFrame(animate);
        return () => cancelAnimationFrame(requestRef.current);
    }, [isActive, color, analyserRef]);

    return (
        <canvas 
            ref={canvasRef} 
            width={400} 
            height={100} 
            className="w-full h-24 opacity-80 pointer-events-none"
        />
    );
};

export default VoiceVisualizer;
