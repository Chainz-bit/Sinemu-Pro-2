import 'leaflet/dist/leaflet.css';
import * as LeafletModule from 'leaflet';

const Leaflet = window.L || LeafletModule;

if (!window.L) {
    window.L = Leaflet;
}

const DEFAULT_CENTER = {
    lat: -6.3264,
    lng: 108.3227,
};

function parseNumber(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).trim();

    if (normalized === '') {
        return null;
    }

    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : null;
}

function isValidLatLng(lat, lng) {
    return Number.isFinite(lat)
        && Number.isFinite(lng)
        && lat >= -90
        && lat <= 90
        && lng >= -180
        && lng <= 180;
}

function normalizeRegionName(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/^kecamatan\s+/i, '')
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ');
}

function parseRegions(value) {
    try {
        const parsed = JSON.parse(value || '[]');
        if (!Array.isArray(parsed)) {
            return new Map();
        }

        return parsed.reduce(function (map, item) {
            const name = normalizeRegionName(item.nama_wilayah || item.name || item.kecamatan);
            const lat = parseNumber(item.lat);
            const lng = parseNumber(item.lng);

            if (name && isValidLatLng(lat, lng)) {
                map.set(name, { lat, lng });
            }

            return map;
        }, new Map());
    } catch (error) {
        return new Map();
    }
}

function initPicker(root) {
    if (!root || root.dataset.pickupPickerReady === 'true' || !Leaflet) {
        return;
    }

    const mapElement = root.querySelector('[data-pickup-map]');
    const form = root.closest('form') || document;
    const latInput = form.querySelector('[name="pickup_lat"]');
    const lngInput = form.querySelector('[name="pickup_lng"]');
    const regionInput = form.querySelector('[name="kecamatan"]');
    const locationButton = root.querySelector('[data-pickup-use-current-location]');
    const messageElement = root.querySelector('[data-pickup-location-message]');

    if (!mapElement || !latInput || !lngInput) {
        return;
    }

    root.dataset.pickupPickerReady = 'true';

    const regions = parseRegions(root.getAttribute('data-regions'));
    const defaultCenter = {
        lat: parseNumber(root.getAttribute('data-default-lat')) ?? DEFAULT_CENTER.lat,
        lng: parseNumber(root.getAttribute('data-default-lng')) ?? DEFAULT_CENTER.lng,
    };
    const initialLat = parseNumber(latInput.value);
    const initialLng = parseNumber(lngInput.value);
    let hasManualPickup = isValidLatLng(initialLat, initialLng);
    let marker = null;

    function getSelectedRegionCenter() {
        if (!regionInput) {
            return null;
        }

        return regions.get(normalizeRegionName(regionInput.value)) || null;
    }

    const initialRegionCenter = getSelectedRegionCenter();
    const initialCenter = hasManualPickup
        ? { lat: initialLat, lng: initialLng }
        : (initialRegionCenter || defaultCenter);

    const map = Leaflet.map(mapElement, {
        zoomControl: true,
        scrollWheelZoom: false,
    }).setView([initialCenter.lat, initialCenter.lng], hasManualPickup ? 16 : 13);

    Leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    function setMarker(lat, lng, shouldPan) {
        if (!isValidLatLng(lat, lng)) {
            return;
        }

        if (!marker) {
            marker = Leaflet.marker([lat, lng]).addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }

        if (shouldPan) {
            map.setView([lat, lng], Math.max(map.getZoom(), 16), {
                animate: true,
                duration: 0.25,
            });
        }
    }

    function clearMarker() {
        if (!marker) {
            return;
        }

        map.removeLayer(marker);
        marker = null;
    }

    function fillInputs(lat, lng) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        latInput.dispatchEvent(new Event('input', { bubbles: true }));
        lngInput.dispatchEvent(new Event('input', { bubbles: true }));
        latInput.dispatchEvent(new Event('change', { bubbles: true }));
        lngInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setMessage(message, type) {
        if (!messageElement) {
            return;
        }

        messageElement.textContent = message || '';
        messageElement.classList.toggle('is-success', type === 'success');
        messageElement.classList.toggle('is-error', type === 'error');
    }

    function useCurrentLocation() {
        if (typeof navigator === 'undefined' || !('geolocation' in navigator)) {
            setMessage('Browser tidak mendukung fitur lokasi.', 'error');
            return;
        }

        const originalLabel = locationButton.textContent.trim() || 'Gunakan Lokasi Saya';
        locationButton.disabled = true;
        locationButton.textContent = 'Mengambil lokasi...';
        setMessage('', null);

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = parseNumber(position.coords.latitude);
                const lng = parseNumber(position.coords.longitude);

                locationButton.disabled = false;
                locationButton.textContent = originalLabel;

                if (!isValidLatLng(lat, lng)) {
                    setMessage('Tidak dapat mengambil lokasi. Pastikan izin lokasi diberikan.', 'error');
                    return;
                }

                hasManualPickup = true;
                fillInputs(lat, lng);
                setMarker(lat, lng, true);

                const accuracy = parseNumber(position.coords.accuracy);
                const accuracyText = Number.isFinite(accuracy)
                    ? ` Akurasi sekitar ${Math.round(accuracy)} meter.`
                    : '';

                setMessage(`Lokasi berhasil digunakan.${accuracyText}`, 'success');
            },
            function () {
                locationButton.disabled = false;
                locationButton.textContent = originalLabel;
                setMessage('Tidak dapat mengambil lokasi. Pastikan izin lokasi diberikan.', 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    }

    function syncFromInputs() {
        const lat = parseNumber(latInput.value);
        const lng = parseNumber(lngInput.value);

        if (latInput.value.trim() === '' && lngInput.value.trim() === '') {
            hasManualPickup = false;
            clearMarker();
            return;
        }

        if (!isValidLatLng(lat, lng)) {
            return;
        }

        hasManualPickup = true;
        setMarker(lat, lng, true);
    }

    if (hasManualPickup) {
        setMarker(initialLat, initialLng, false);
    }

    map.on('click', function (event) {
        const lat = event.latlng.lat;
        const lng = event.latlng.lng;

        hasManualPickup = true;
        fillInputs(lat, lng);
        setMarker(lat, lng, true);
    });

    latInput.addEventListener('input', syncFromInputs);
    lngInput.addEventListener('input', syncFromInputs);
    latInput.addEventListener('change', syncFromInputs);
    lngInput.addEventListener('change', syncFromInputs);

    if (regionInput) {
        regionInput.addEventListener('change', function () {
            if (hasManualPickup) {
                return;
            }

            const center = getSelectedRegionCenter();
            if (!center) {
                map.setView([defaultCenter.lat, defaultCenter.lng], 12);
                return;
            }

            map.setView([center.lat, center.lng], 13, {
                animate: true,
                duration: 0.25,
            });
        });
    }

    if (locationButton) {
        locationButton.addEventListener('click', useCurrentLocation);
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 150);
}

function initPickupLocationPickers() {
    document.querySelectorAll('[data-pickup-location-picker]').forEach(initPicker);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPickupLocationPickers);
} else {
    initPickupLocationPickers();
}
