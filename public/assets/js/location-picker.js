/*
 * Location picker: adds a "Pin on map" button to every input[data-location-picker].
 * Search and reverse lookup use OpenStreetMap Nominatim; the map uses MapLibre GL with OpenFreeMap
 * vector tiles (no API key, no usage limits).
 * The field stays a plain text input, so venues such as "Online via Zoom" can still be typed.
 *
 * Options (data attributes on the input):
 *   data-location-picker="ph"     limit search to the Philippines (default)
 *   data-location-picker="world"  search worldwide (conferences, presentations)
 *
 * Nominatim usage policy: no search-as-you-type, at most one request per second.
 */
(() => {
    'use strict';

    const MAPLIBRE_BASE = 'https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1';
    const MAP_STYLE = 'https://tiles.openfreemap.org/styles/liberty';
    const NOMINATIM = 'https://nominatim.openstreetmap.org';
    const DEFAULT_VIEW = { lat: 9.7392, lng: 118.7353, zoom: 8 }; // Palawan
    const MIN_REQUEST_GAP_MS = 1100;

    let maplibrePromise = null;
    let lastRequestAt = 0;
    let modal = null;
    let state = { input: null, scope: 'ph', map: null, marker: null, picked: null };

    const loadMapLibre = () => {
        if (window.maplibregl) {
            return Promise.resolve(window.maplibregl);
        }
        if (maplibrePromise) {
            return maplibrePromise;
        }
        maplibrePromise = new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = `${MAPLIBRE_BASE}/maplibre-gl.css`;
            document.head.appendChild(css);
            const script = document.createElement('script');
            script.src = `${MAPLIBRE_BASE}/maplibre-gl.js`;
            script.onload = () => resolve(window.maplibregl);
            script.onerror = () => {
                maplibrePromise = null;
                reject(new Error('The map could not be loaded.'));
            };
            document.head.appendChild(script);
        });
        return maplibrePromise;
    };

    const nominatim = async (path, params) => {
        const wait = lastRequestAt + MIN_REQUEST_GAP_MS - Date.now();
        if (wait > 0) {
            await new Promise((resolve) => setTimeout(resolve, wait));
        }
        lastRequestAt = Date.now();
        const query = new URLSearchParams({ format: 'jsonv2', addressdetails: '1', 'accept-language': 'en', ...params });
        const response = await fetch(`${NOMINATIM}/${path}?${query.toString()}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            throw new Error(`Location service returned ${response.status}.`);
        }
        return response.json();
    };

    // Philippine order: place, barangay, city or municipality, province. Country only outside the Philippines.
    const formatAddress = (result) => {
        const a = result?.address || {};
        const parts = [];
        const push = (value) => {
            const text = String(value || '').trim();
            if (text !== '' && !parts.some((part) => part.toLowerCase() === text.toLowerCase())) {
                parts.push(text);
            }
        };
        // Streets and buildings without a name of their own are left out; barangay level is what the forms record.
        const placeName = result?.name && result.category !== 'highway' && result.category !== 'boundary'
            && !['road', 'house', 'residential', 'administrative', 'building'].includes(result.addresstype)
            ? result.name : '';
        push(placeName);
        push(a.village || a.suburb || a.quarter || a.neighbourhood || a.hamlet);
        push(a.city || a.town || a.municipality);
        push(a.province || a.state || a.county);
        if (a.country_code && a.country_code !== 'ph') {
            push(a.country);
        }
        return parts.length > 0 ? parts.join(', ') : String(result?.display_name || '');
    };

    const el = (tag, attrs = {}, text = '') => {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        if (text !== '') {
            node.textContent = text;
        }
        return node;
    };

    const setStatus = (message, isError = false) => {
        const status = modal.querySelector('.lp-status');
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const setPicked = (lat, lng, address) => {
        state.picked = { lat, lng };
        const addressInput = modal.querySelector('.lp-address');
        addressInput.value = address;
        modal.querySelector('.lp-use').disabled = address.trim() === '';
        modal.querySelector('.lp-coords').textContent = `Coordinates: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    };

    const placeMarker = (lat, lng, zoom) => {
        if (!state.marker) {
            state.marker = new window.maplibregl.Marker({ draggable: true, color: '#047857' })
                .setLngLat([lng, lat])
                .addTo(state.map);
            state.marker.getElement().setAttribute('title', 'Selected location. Drag to adjust.');
            state.marker.on('dragend', () => {
                const pos = state.marker.getLngLat();
                reverseLookup(pos.lat, pos.lng);
            });
        } else {
            state.marker.setLngLat([lng, lat]);
        }
        state.map.easeTo({ center: [lng, lat], zoom: zoom ?? Math.max(state.map.getZoom(), 15) });
    };

    const reverseLookup = async (lat, lng) => {
        setPicked(lat, lng, modal.querySelector('.lp-address').value);
        setStatus('Looking up the address of the pinned point...');
        try {
            const result = await nominatim('reverse', { lat: String(lat), lon: String(lng), zoom: '18' });
            if (result && !result.error) {
                setPicked(lat, lng, formatAddress(result));
                setStatus('Point pinned. Review the address below before using it.');
            } else {
                setStatus('No address was found for this point. Enter the address below.', true);
            }
        } catch (error) {
            setStatus('The address lookup is unavailable. Enter the address below.', true);
        }
    };

    const renderResults = (results) => {
        const list = modal.querySelector('.lp-results');
        list.replaceChildren();
        results.forEach((result) => {
            const item = el('li');
            const button = el('button', { type: 'button', class: 'lp-result' });
            button.appendChild(el('strong', {}, formatAddress(result)));
            button.appendChild(el('span', {}, String(result.display_name || '')));
            button.addEventListener('click', () => {
                const lat = Number(result.lat);
                const lng = Number(result.lon);
                placeMarker(lat, lng, 16);
                setPicked(lat, lng, formatAddress(result));
                list.replaceChildren();
                setStatus('Location selected. Drag the pin or click the map to adjust it.');
            });
            item.appendChild(button);
            list.appendChild(item);
        });
    };

    const runSearch = async () => {
        const query = modal.querySelector('.lp-query').value.trim();
        if (query === '') {
            setStatus('Enter a place, barangay, or municipality to search.', true);
            return;
        }
        setStatus('Searching...');
        try {
            const params = { q: query, limit: '6' };
            if (state.scope !== 'world') {
                params.countrycodes = 'ph';
            }
            const results = await nominatim('search', params);
            renderResults(Array.isArray(results) ? results : []);
            setStatus(results.length > 0
                ? 'Select the matching result, or click the map to pin the exact point.'
                : 'No matching places were found. Refine the search or click the map to pin the point.', results.length === 0);
        } catch (error) {
            setStatus('The location search is unavailable. Pin the point on the map or enter the address below.', true);
        }
    };

    const buildModal = () => {
        modal = el('div', { class: 'lp-backdrop', hidden: '' });
        modal.innerHTML = `
            <div class="lp-dialog" role="dialog" aria-modal="true" aria-labelledby="lp-title">
                <header class="lp-head">
                    <h2 id="lp-title">Pin Location on Map</h2>
                    <button type="button" class="lp-close" aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </header>
                <form class="lp-search" novalidate>
                    <input type="search" class="lp-query" placeholder="Search a place, barangay, or municipality" aria-label="Search location" autocomplete="off">
                    <button type="submit" class="btn btn-sm">Search</button>
                </form>
                <ul class="lp-results" aria-label="Search results"></ul>
                <div class="lp-map" aria-label="Map. Click to pin a location."></div>
                <p class="lp-status" role="status"></p>
                <label class="lp-address-label" for="lp-address">Address to be saved</label>
                <input type="text" id="lp-address" class="lp-address" maxlength="255" placeholder="Barangay, municipality or city, province">
                <p class="lp-coords"></p>
                <footer class="lp-actions">
                    <button type="button" class="btn btn-outline btn-sm lp-cancel">Cancel</button>
                    <button type="button" class="btn btn-sm lp-use" disabled>Use This Location</button>
                </footer>
            </div>`;
        document.body.appendChild(modal);

        modal.querySelector('.lp-search').addEventListener('submit', (event) => {
            event.preventDefault();
            runSearch();
        });
        modal.querySelector('.lp-address').addEventListener('input', (event) => {
            modal.querySelector('.lp-use').disabled = event.target.value.trim() === '';
        });
        modal.querySelector('.lp-close').addEventListener('click', close);
        modal.querySelector('.lp-cancel').addEventListener('click', close);
        modal.querySelector('.lp-use').addEventListener('click', () => {
            const value = modal.querySelector('.lp-address').value.trim();
            if (state.input && value !== '') {
                state.input.value = value;
                if (state.picked) {
                    state.input.dataset.lat = String(state.picked.lat);
                    state.input.dataset.lng = String(state.picked.lng);
                }
                state.input.dispatchEvent(new Event('input', { bubbles: true }));
                state.input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            close();
        });
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });
        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        });
    };

    function close() {
        if (!modal || modal.hidden) {
            return;
        }
        modal.hidden = true;
        document.documentElement.classList.remove('lp-open');
        state.input?.focus();
    }

    const open = async (input) => {
        if (!modal) {
            buildModal();
        }
        state.input = input;
        state.scope = input.dataset.locationPicker === 'world' ? 'world' : 'ph';
        state.picked = null;
        modal.querySelector('.lp-results').replaceChildren();
        modal.querySelector('.lp-query').value = input.value.trim();
        modal.querySelector('.lp-address').value = input.value.trim();
        modal.querySelector('.lp-use').disabled = input.value.trim() === '';
        modal.querySelector('.lp-coords').textContent = '';
        modal.hidden = false;
        document.documentElement.classList.add('lp-open');
        modal.querySelector('.lp-query').focus();
        setStatus('Search for the place, then select a result. You may also click the map to pin the exact point.');

        try {
            const maplibregl = await loadMapLibre();
            if (!state.map) {
                // The OpenFreeMap style carries its own OpenFreeMap / OpenMapTiles / OpenStreetMap attribution.
                state.map = new maplibregl.Map({
                    container: modal.querySelector('.lp-map'),
                    style: MAP_STYLE,
                    center: [DEFAULT_VIEW.lng, DEFAULT_VIEW.lat],
                    zoom: DEFAULT_VIEW.zoom,
                    maxZoom: 19,
                    attributionControl: { compact: true },
                    dragRotate: false,
                    pitchWithRotate: false,
                });
                state.map.touchZoomRotate.disableRotation();
                state.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
                state.map.on('click', (event) => {
                    placeMarker(event.lngLat.lat, event.lngLat.lng);
                    reverseLookup(event.lngLat.lat, event.lngLat.lng);
                });
                state.map.on('error', () => {
                    setStatus('Some map tiles could not be loaded. Search still works, or enter the address below.', true);
                });
            }
            setTimeout(() => state.map.resize(), 50);
            const lat = Number(input.dataset.lat);
            const lng = Number(input.dataset.lng);
            if (Number.isFinite(lat) && Number.isFinite(lng) && input.dataset.lat) {
                placeMarker(lat, lng, 16);
                setPicked(lat, lng, input.value.trim());
            } else {
                if (state.marker) {
                    state.marker.remove();
                    state.marker = null;
                }
                state.map.jumpTo({ center: [DEFAULT_VIEW.lng, DEFAULT_VIEW.lat], zoom: DEFAULT_VIEW.zoom });
                if (input.value.trim() !== '') {
                    runSearch();
                }
            }
        } catch (error) {
            setStatus('The map could not be loaded. Check the internet connection, or enter the address below.', true);
        }
    };

    const enhance = (root) => {
        const inputs = root.querySelectorAll
            ? root.querySelectorAll('input[data-location-picker]')
            : [];
        inputs.forEach((input) => {
            // Rows cloned from an existing row already carry the wrapper and button.
            if (input.readOnly || input.disabled || input.closest('.lp-field')) {
                return;
            }
            const wrap = el('span', { class: 'lp-field' });
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            const button = el('button', {
                type: 'button',
                class: 'lp-trigger',
                title: 'Pin location on map',
                'aria-label': 'Pin location on map',
            });
            button.innerHTML = '<i class="fas fa-location-dot" aria-hidden="true"></i>';
            wrap.appendChild(button);
        });
    };

    const start = () => {
        enhance(document);
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest?.('.lp-trigger');
            const input = trigger?.closest('.lp-field')?.querySelector('input[data-location-picker]');
            if (input && !input.readOnly && !input.disabled) {
                open(input);
            }
        });
        // Repeater rows and templates added later get the button too.
        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) {
                    return;
                }
                if (node.matches?.('input[data-location-picker]')) {
                    enhance(node.parentNode);
                } else {
                    enhance(node);
                }
            }));
        }).observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
