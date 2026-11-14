/**
 * Payments scanner: reads a SmartLabel sticker with the phone camera and opens
 * that product's payments. Uses the built-in BarcodeDetector where available
 * (Chrome on Android) and falls back to jsQR, loaded only when needed (iPhone).
 */
const TOKEN = /\/p\/([2-9A-HJ-NP-Z]{7})(?:[/?#]|$)/i;

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#scanner').forEach(root => {
    const video = root.querySelector('video');
    const area = root.querySelector('[data-scan-area]');
    const startBtn = root.querySelector('[data-scan-start]');
    const errorBox = root.querySelector('[data-scan-error]');
    let stream = null;
    let timer = null;
    let detect = null;

    const showError = message => {
      errorBox.textContent = message;
      errorBox.classList.toggle('d-none', !message);
    };

    const stop = () => {
      clearTimeout(timer);
      stream?.getTracks().forEach(track => track.stop());
      stream = null;
      area.classList.add('d-none');
      startBtn.classList.remove('d-none');
    };

    const open = code => {
      stop();
      window.location = root.dataset.productUrl.replace('__CODE__', encodeURIComponent(code));
    };

    const makeDetector = async () => {
      if ('BarcodeDetector' in window) {
        const formats = await window.BarcodeDetector.getSupportedFormats().catch(() => []);
        if (formats.includes('qr_code')) {
          const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
          return async () => (await detector.detect(video).catch(() => []))[0]?.rawValue ?? null;
        }
      }

      const { default: jsQR } = await import('jsqr');
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      return async () => {
        // Downscale: jsQR is fast enough on a 640px frame even on budget phones.
        const scale = Math.min(1, 640 / video.videoWidth);
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        return jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' })?.data ?? null;
      };
    };

    const tick = async () => {
      if (!stream) return;
      if (video.readyState >= 2 && video.videoWidth) {
        const text = await detect();
        const match = text && String(text).match(TOKEN);
        if (match) return open(match[1].toUpperCase());
        if (text) showError(root.dataset.errorForeign);
      }
      timer = setTimeout(tick, 250);
    };

    startBtn.addEventListener('click', async () => {
      showError('');
      if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
        return showError(root.dataset.errorInsecure);
      }
      try {
        detect ??= await makeDetector();
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
        video.srcObject = stream;
        area.classList.remove('d-none');
        startBtn.classList.add('d-none');
        await video.play();
        tick();
      } catch (e) {
        stop();
        showError(root.dataset.errorCamera);
      }
    });

    root.querySelector('[data-scan-stop]').addEventListener('click', stop);
    window.addEventListener('pagehide', stop);
  });
});
