(function () {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMiniPreview);
    } else {
        initMiniPreview();
    }

    function initMiniPreview() {
        const drawer = document.getElementById('dd-wpc-drawer');
        if (!drawer) {
            return;
        }

        const stage = document.getElementById('dd-wpc-preview-stage');
        const scrollBtn = drawer.querySelector('.dd-wpc-btn-scroll');
        const toggleBtn = drawer.querySelector('.dd-wpc-btn-toggle');
        const customToggleImg = drawer.querySelector('.dd-wpc-icon-toggle');
        const zoomOutBtn = drawer.querySelector('.dd-wpc-btn-zoom-out');
        const zoomInBtn = drawer.querySelector('.dd-wpc-btn-zoom-in');
        const zoomLevelLabel = drawer.querySelector('.dd-wpc-zoom-level');

        const ZOOM_MIN = 10;
        const ZOOM_MAX = 100;
        const ZOOM_STEP = 10;
        const ZOOM_STORAGE_KEY = 'ddWpcPreviewZoom';

        let zoom = ZOOM_MIN;
        let mainPreviewElement = null;
        let observer = null;
        let isDrawerVisible = false;
        let pendingUpdate = true;
        let syncScheduled = false;
        let scrollTick = false;
        let setupRetries = 0;
        let attachRetries = 0;
        const MAX_RETRIES = 15;

        function normalizeZoom(value) {
            const parsed = parseInt(value, 10);
            if (isNaN(parsed)) {
                return ZOOM_MIN;
            }
            const snapped = Math.round(parsed / ZOOM_STEP) * ZOOM_STEP;
            return Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, snapped));
        }

        function readStoredZoom() {
            try {
                const stored = window.localStorage.getItem(ZOOM_STORAGE_KEY);
                if (stored === null) {
                    return ZOOM_MIN;
                }
                return normalizeZoom(stored);
            } catch (e) {
                return ZOOM_MIN;
            }
        }

        function persistZoom(value) {
            try {
                window.localStorage.setItem(ZOOM_STORAGE_KEY, String(value));
            } catch (e) {
                // Storage unavailable (private mode, disabled cookies) - zoom still works for this visit.
            }
        }

        function applyZoom(value) {
            zoom = normalizeZoom(value);
            drawer.style.setProperty('--dd-wpc-zoom', zoom + 'vw');
            drawer.setAttribute('data-dd-wpc-zoom', String(zoom));

            if (zoomLevelLabel) {
                zoomLevelLabel.textContent = zoom + '%';
            }
            if (zoomOutBtn) {
                zoomOutBtn.disabled = zoom <= ZOOM_MIN;
            }
            if (zoomInBtn) {
                zoomInBtn.disabled = zoom >= ZOOM_MAX;
            }
        }

        function changeZoom(delta) {
            applyZoom(zoom + delta);
            persistZoom(zoom);
        }

        function findMainPreviewElement() {
            return (
                document.querySelector('.wpc-preview-parent-wrap') ||
                document.querySelector('[data-configurator-view]') ||
                document.querySelector('.wpc-configurator-view') ||
                document.querySelector('[data-configurator]')
            );
        }

        function getConfigStore() {
            if (typeof window.wpc !== 'undefined' && typeof window.wpc.getStore === 'function') {
                const confEl = document.querySelector('[data-configurator][data-config-id]');
                const cid = confEl ? confEl.getAttribute('data-config-id') : null;
                if (cid) {
                    return window.wpc.getStore(cid);
                }
            }
            return null;
        }

        function findActivePreviewInner() {
            const store = getConfigStore();
            if (store && store.activeView) {
                const targetViewEl = document.querySelector(`[data-preview-inner][data-type="${store.activeView}"]`);
                if (targetViewEl) {
                    return targetViewEl;
                }
            }

            const inActiveItem = document.querySelector('.wpc-configurator .wpc-carousel-item.active [data-preview-inner]');
            if (inActiveItem) {
                return inActiveItem;
            }

            const inActiveContainer = document.querySelector('.wpc-configurator-view .active[data-preview-inner], [data-configurator-view] .active[data-preview-inner], .active > [data-preview-inner]');
            if (inActiveContainer) {
                return inActiveContainer;
            }

            const allInners = document.querySelectorAll('[data-preview-inner]');
            for (let i = 0; i < allInners.length; i++) {
                const el = allInners[i];
                const rect = el.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0) {
                    return el;
                }
            }

            return document.querySelector('[data-preview-inner]');
        }

        function checkVisibilityFallback() {
            if (!mainPreviewElement) {
                mainPreviewElement = findMainPreviewElement();
            }

            if (!mainPreviewElement) {
                return;
            }

            const rect = mainPreviewElement.getBoundingClientRect();
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;
            const isInView = rect.bottom > 50 && rect.top < (windowHeight - 50);

            if (!isInView) {
                if (!isDrawerVisible) {
                    isDrawerVisible = true;
                    drawer.classList.add('is-visible');
                    drawer.setAttribute('aria-hidden', 'false');
                    if (pendingUpdate) {
                        syncPreviewClone();
                    }
                }
            } else {
                if (isDrawerVisible) {
                    isDrawerVisible = false;
                    drawer.classList.remove('is-visible');
                    drawer.setAttribute('aria-hidden', 'true');
                }
            }
        }

        function setupIntersectionObserver() {
            mainPreviewElement = findMainPreviewElement();
            if (!mainPreviewElement) {
                setupRetries++;
                if (setupRetries <= MAX_RETRIES) {
                    setTimeout(setupIntersectionObserver, 300);
                }
                return;
            }

            if (window.IntersectionObserver) {
                if (observer) {
                    observer.disconnect();
                }

                observer = new IntersectionObserver(
                    function (entries) {
                        for (let i = 0; i < entries.length; i++) {
                            const entry = entries[i];
                            if (!entry.isIntersecting) {
                                isDrawerVisible = true;
                                drawer.classList.add('is-visible');
                                drawer.setAttribute('aria-hidden', 'false');

                                if (pendingUpdate) {
                                    syncPreviewClone();
                                }
                            } else {
                                isDrawerVisible = false;
                                drawer.classList.remove('is-visible');
                                drawer.setAttribute('aria-hidden', 'true');
                            }
                        }
                    },
                    {
                        root: null,
                        threshold: [0, 0.1, 0.5],
                    }
                );

                observer.observe(mainPreviewElement);
            } else {
                window.addEventListener('scroll', function () {
                    if (!scrollTick) {
                        window.requestAnimationFrame(function () {
                            checkVisibilityFallback();
                            scrollTick = false;
                        });
                        scrollTick = true;
                    }
                }, { passive: true });
            }

            window.addEventListener('resize', checkVisibilityFallback, { passive: true });
            window.addEventListener('orientationchange', checkVisibilityFallback, { passive: true });

            checkVisibilityFallback();
        }

        setupIntersectionObserver();

        if (scrollBtn) {
            scrollBtn.addEventListener('click', function (e) {
                e.preventDefault();

                if (!mainPreviewElement) {
                    mainPreviewElement = findMainPreviewElement();
                }

                if (mainPreviewElement) {
                    mainPreviewElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const isCollapsed = drawer.classList.toggle('is-collapsed');
                toggleBtn.setAttribute('aria-expanded', !isCollapsed);

                if (customToggleImg) {
                    customToggleImg.style.transform = isCollapsed ? 'rotate(180deg)' : 'none';
                }
            });
        }

        applyZoom(readStoredZoom());

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                changeZoom(-ZOOM_STEP);
            });
        }

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function (e) {
                e.preventDefault();
                changeZoom(ZOOM_STEP);
            });
        }

        function extractImageSource(imgElement, containerElement) {
            const rawSrc =
                imgElement.getAttribute('data-src') ||
                imgElement.getAttribute('data-litespeed-src') ||
                imgElement.getAttribute('data-lazy-src') ||
                imgElement.getAttribute('data-orig-src') ||
                imgElement.getAttribute('src') ||
                imgElement.src;

            if (rawSrc && rawSrc.startsWith('data:image') && containerElement) {
                const noscript = containerElement.querySelector('noscript');
                if (noscript) {
                    const match = noscript.textContent.match(/src=["']([^"']+)["']/i);
                    if (match && match[1]) {
                        return match[1];
                    }
                }
            }

            return rawSrc;
        }

        function isLayerActive(element) {
            if (element.classList.contains('active') || element.classList.contains('wpc-always-show')) {
                return true;
            }

            const inlineDisplay = element.style.display;
            const inlineVisibility = element.style.visibility;
            const inlineOpacity = element.style.opacity;

            if (inlineDisplay === 'block' || inlineDisplay === 'flex' || inlineDisplay === 'inline-block') {
                return inlineVisibility !== 'hidden' && inlineOpacity !== '0';
            }

            if (inlineDisplay === 'none' || inlineVisibility === 'hidden' || inlineOpacity === '0') {
                return false;
            }

            const compStyle = window.getComputedStyle(element);
            return compStyle.display !== 'none' && compStyle.visibility !== 'hidden' && compStyle.opacity !== '0';
        }

        function syncPreviewClone() {
            if (!stage) {
                return;
            }

            const activeInner = findActivePreviewInner();
            if (!activeInner) {
                pendingUpdate = true;
                return;
            }

            const store = getConfigStore();
            let aspect = 1;
            if (store && store.maxImageWidth && store.maxImageHeight) {
                aspect = store.maxImageHeight / store.maxImageWidth;
            } else {
                const origRect = activeInner.getBoundingClientRect();
                if (origRect.width > 0 && origRect.height > 0) {
                    aspect = origRect.height / origRect.width;
                }
            }

            if (aspect > 0) {
                drawer.style.setProperty('--dd-wpc-aspect-ratio', (1 / aspect).toString());
            }

            const clonedInner = activeInner.cloneNode(true);
            clonedInner.removeAttribute('id');
            clonedInner.className = 'dd-wpc-preview-inner';

            const origSubsets = activeInner.querySelectorAll('.subset');
            const cloneSubsets = clonedInner.querySelectorAll('.subset');

            for (let s = 0; s < origSubsets.length; s++) {
                const origSub = origSubsets[s];
                const cloneSub = cloneSubsets[s];
                if (!cloneSub) continue;

                const isVisible = isLayerActive(origSub);
                cloneSub.className = isVisible ? 'dd-wpc-layer is-active' : 'dd-wpc-layer';
                cloneSub.removeAttribute('style');

                const origImg = origSub.querySelector('img');
                const cloneImg = cloneSub.querySelector('img');
                if (origImg && cloneImg) {
                    const src = extractImageSource(origImg, origSub);
                    if (src) {
                        cloneImg.src = src;
                        cloneImg.removeAttribute('data-src');
                        cloneImg.removeAttribute('data-litespeed-src');
                        cloneImg.removeAttribute('data-lazyloaded');
                        cloneImg.removeAttribute('loading');
                    }
                    cloneImg.className = 'dd-wpc-layer-img';
                    cloneImg.removeAttribute('style');
                }
            }

            const removeSelectors = [
                'template',
                'noscript',
                '[data-hotspot]',
                '.wpc-hotspot',
                '.wpc-show-details',
                '.wpc-carousel-nav',
                '.wpc-carousel-dots',
            ];

            for (let r = 0; r < removeSelectors.length; r++) {
                const els = clonedInner.querySelectorAll(removeSelectors[r]);
                for (let j = 0; j < els.length; j++) {
                    els[j].remove();
                }
            }

            const allNodes = clonedInner.querySelectorAll('*');
            for (let k = 0; k < allNodes.length; k++) {
                const node = allNodes[k];
                node.removeAttribute('id');
                const attrs = node.getAttributeNames();
                for (let a = 0; a < attrs.length; a++) {
                    if (attrs[a].startsWith('x-')) {
                        node.removeAttribute(attrs[a]);
                    }
                }
            }

            if (typeof stage.replaceChildren === 'function') {
                stage.replaceChildren(clonedInner);
            } else {
                stage.innerHTML = '';
                stage.appendChild(clonedInner);
            }

            pendingUpdate = false;
        }

        function scheduleSync() {
            if (!isDrawerVisible) {
                pendingUpdate = true;
                return;
            }

            if (syncScheduled) {
                return;
            }

            syncScheduled = true;
            window.requestAnimationFrame(function () {
                syncPreviewClone();
                syncScheduled = false;
            });
        }

        if (typeof window.wpc !== 'undefined' && typeof window.wpc.add_action === 'function') {
            const wpcEvents = [
                'wpc_action_layer_selected',
                'wpc_action_layer_deselected',
                'wpc_action_group_opened',
                'wpc_data_changeimage',
                'wpc_applied_components',
                'wpc_carousel_changed',
                'wpc_preview_slide_changed',
                'wpc_after_canvas_created',
            ];

            wpcEvents.forEach(function (eventName) {
                window.wpc.add_action(eventName, function () {
                    scheduleSync();
                });
            });
        }

        function attachPreviewObserver() {
            const previewWrap =
                document.querySelector('.wpc-configurator-view') ||
                document.querySelector('[data-configurator-view]') ||
                document.querySelector('.wpc-preview-parent-wrap');

            if (!previewWrap) {
                attachRetries++;
                if (attachRetries <= MAX_RETRIES) {
                    setTimeout(attachPreviewObserver, 400);
                }
                return;
            }

            const mutObserver = new MutationObserver(function () {
                scheduleSync();
            });

            mutObserver.observe(previewWrap, {
                attributes: true,
                attributeFilter: ['class', 'style', 'src'],
                subtree: true,
            });

            setTimeout(syncPreviewClone, 300);
            setTimeout(syncPreviewClone, 800);
            setTimeout(syncPreviewClone, 1500);
        }

        attachPreviewObserver();
    }
})();
