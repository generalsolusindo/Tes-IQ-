const GEOLOCATION_ERROR_MESSAGES = {
    1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser untuk absen.',
    2: 'Lokasi tidak dapat dideteksi. Coba lagi di area terbuka.',
    3: 'Waktu permintaan lokasi habis. Coba lagi.',
};

// Matches the backend's MAX_GPS_ACCURACY_METERS (AbsensiController) — kept
// as a separate constant since frontend/backend can't share PHP/JS code.
const MAX_GPS_ACCURACY_METERS = 50;
const LOCATION_ACQUIRE_TIMEOUT_MS = 15000;

/**
 * A phone's first GPS fix after requesting location is often a coarse
 * network/cell-based estimate (accuracy 60-100m+); the chip only narrows
 * to an accurate fix after a few seconds of satellite lock. A one-shot
 * getCurrentPosition() call was returning that first coarse fix, forcing
 * karyawan to retry manually. This watches for updates until one is
 * accurate enough (or the timeout hits), returning the best fix seen.
 */
export function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Perangkat Anda tidak mendukung geolokasi.'));
            return;
        }

        let best = null;
        let watchId = null;

        const cleanup = () => {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
            clearTimeout(timeoutId);
        };

        const timeoutId = setTimeout(() => {
            cleanup();

            if (best) {
                resolve(best);
            } else {
                reject(new Error(GEOLOCATION_ERROR_MESSAGES[3]));
            }
        }, LOCATION_ACQUIRE_TIMEOUT_MS);

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                if (!best || position.coords.accuracy < best.coords.accuracy) {
                    best = position;
                }

                if (position.coords.accuracy <= MAX_GPS_ACCURACY_METERS) {
                    cleanup();
                    resolve(position);
                }
            },
            (error) => {
                cleanup();

                if (best) {
                    resolve(best);
                    return;
                }

                reject(
                    new Error(
                        GEOLOCATION_ERROR_MESSAGES[error.code] ??
                            'Gagal mendapatkan lokasi.',
                    ),
                );
            },
            { enableHighAccuracy: true, maximumAge: 0 },
        );
    });
}

/**
 * Opens the device camera directly (not the photo gallery) via a
 * throwaway file input, so a selfie can't be swapped for an old photo.
 * Must be called synchronously from a user gesture (e.g. a button's
 * onClick) or some browsers will silently refuse to open the camera.
 */
export function captureSelfie() {
    return new Promise((resolve, reject) => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'user';

        input.onchange = () => {
            const file = input.files?.[0];

            if (file) {
                resolve(file);
            } else {
                reject(new Error('Foto tidak diambil.'));
            }
        };

        input.click();
    });
}

export function buildAbsensiFormData(photo, position) {
    const formData = new FormData();

    formData.append('latitude', String(position.coords.latitude));
    formData.append('longitude', String(position.coords.longitude));
    formData.append('accuracy', String(position.coords.accuracy));
    formData.append('photo', photo);

    return formData;
}
