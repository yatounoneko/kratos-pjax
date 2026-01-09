(function() {
    'use strict';

    function isDesktop() {
        return window.innerWidth >= 768;
    }

    // 如果不是桌面，直接不執行
    if (!isDesktop()) return;

    const THRESHOLD = 30;
    const SCROLLBAR_WIDTH = 12;

    let scrollbars = [];
    let hideTimeouts = {};
    let isInitialized = false;

    // 隱藏全局原生滾動條
    function hideNativeScrollbars() {
        if (document.getElementById('native-scrollbar-hide')) return;
        const style = document.createElement('style');
        style.id = 'native-scrollbar-hide';
        style.textContent = `
            html, body {
                scrollbar-gutter: stable !important;
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }
            html::-webkit-scrollbar, body::-webkit-scrollbar {
                width: 0 !important;
                height: 0 !important;
            }
            textarea::-webkit-scrollbar,
            iframe::-webkit-scrollbar,
            [style*="overflow"]::-webkit-scrollbar {
                width: 8px !important;
            }
            textarea, iframe, [style*="overflow"] {
                scrollbar-width: thin !important;
            }
        `;
        document.head.appendChild(style);
    }

    function createScrollbar(target) {
        if (target.querySelector('.fake-scrollbar')) return target.querySelector('.fake-scrollbar');

        const scrollbar = document.createElement('div');
        scrollbar.className = 'fake-scrollbar';
        scrollbar.innerHTML = '<div class="fake-thumb"></div>';

        const isGlobal = target === document.documentElement || target === document.body;

        if (isGlobal) {
            scrollbar.style.cssText = `
                position: fixed; top: 0; right: 0; width: ${SCROLLBAR_WIDTH}px;
                height: 100vh; z-index: 2147483647; pointer-events: none;
            `;
            scrollbar.dataset.targetSelector = 'html';
        } else {
            scrollbar.style.cssText = `
                position: absolute; top: 0; right: 0; width: ${SCROLLBAR_WIDTH}px;
                height: 100%; z-index: 9999; pointer-events: none;
            `;
            target.style.position = target.style.position === 'static' ? 'relative' : target.style.position;

            target.addEventListener('mouseenter', () => {
                if (isDesktop()) {
                    scrollbar.classList.add('visible');
                    scrollbar.style.pointerEvents = 'auto';
                }
            });
            target.addEventListener('mouseleave', () => {
                scrollbar.classList.remove('visible');
                scrollbar.style.pointerEvents = 'none';
            });
        }

        target.appendChild(scrollbar);
        scrollbars.push(scrollbar);
        return scrollbar;
    }

    function updateScrollbar(scrollbar) {
        if (!isDesktop()) return;

        const thumb = scrollbar.querySelector('.fake-thumb');
        const target = scrollbar.dataset.targetSelector === 'html' ? document.documentElement : scrollbar.parentNode;

        const scrollTop = target.scrollTop;
        const clientHeight = target.clientHeight;
        const scrollHeight = target.scrollHeight - clientHeight;

        if (scrollHeight <= 0) {
            scrollbar.classList.remove('visible');
            scrollbar.style.pointerEvents = 'none';
            return;
        }

        const ratio = scrollTop / scrollHeight;
        const thumbHeight = Math.max((clientHeight / target.scrollHeight) * clientHeight, 40);
        thumb.style.height = `${thumbHeight}px`;
        thumb.style.top = `${ratio * (clientHeight - thumbHeight)}px`;

        scrollbar.classList.add('visible');
        scrollbar.style.pointerEvents = 'auto';
    }

    function initScrollbars() {
        if (!isDesktop() || isInitialized) return;
        isInitialized = true;

        hideNativeScrollbars();
        createScrollbar(document.documentElement);

        document.querySelectorAll('textarea, iframe, [style*="overflow"], .scrollable').forEach(el => {
            const hasScroll = el.scrollHeight > el.clientHeight || el.scrollWidth > el.clientWidth;
            if (hasScroll) createScrollbar(el);
        });

        scrollbars.forEach(updateScrollbar);
    }

    // 事件綁定
    document.addEventListener('mousemove', (e) => {
        if (!isDesktop()) return;
        const nearRight = window.innerWidth - e.clientX <= THRESHOLD;
        scrollbars.forEach(sb => {
            if (nearRight) {
                updateScrollbar(sb);
                clearTimeout(hideTimeouts[sb.dataset.targetSelector]);
            } else {
                clearTimeout(hideTimeouts[sb.dataset.targetSelector]);
                hideTimeouts[sb.dataset.targetSelector] = setTimeout(() => {
                    sb.classList.remove('visible');
                    sb.style.pointerEvents = 'none';
                }, 800);
            }
        });
    });

    window.addEventListener('scroll', () => {
        if (!isDesktop()) return;
        scrollbars.forEach(sb => {
            updateScrollbar(sb);
            clearTimeout(hideTimeouts[sb.dataset.targetSelector]);
            hideTimeouts[sb.dataset.targetSelector] = setTimeout(() => {
                sb.classList.remove('visible');
                sb.style.pointerEvents = 'none';
            }, 1200);
        });
    }, { passive: true });

    document.addEventListener('mousedown', (e) => {
        if (!isDesktop() || !e.target.classList.contains('fake-thumb')) return;
        // 拖動邏輯保持不變...
        e.preventDefault();
        const thumb = e.target;
        const scrollbar = thumb.parentNode;
        const target = scrollbar.dataset.targetSelector === 'html' ? document.documentElement : scrollbar.parentNode;
        const rect = scrollbar.getBoundingClientRect();
        const startY = e.clientY;
        const startTop = parseFloat(thumb.style.top) || 0;

        const drag = (e) => {
            const deltaY = e.clientY - startY;
            const maxY = rect.height - thumb.offsetHeight;
            const newTop = Math.max(0, Math.min(maxY, startTop + deltaY));
            const ratio = newTop / maxY;
            target.scrollTop = ratio * (target.scrollHeight - target.clientHeight);
            updateScrollbar(scrollbar);
        };

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', () => document.removeEventListener('mousemove', drag), { once: true });
    });

    // 初始化和 resize
    function handleResize() {
        scrollbars = [];  // 清空舊的
        document.querySelectorAll('.fake-scrollbar').forEach(el => el.remove());
        const hideStyle = document.getElementById('native-scrollbar-hide');
        if (hideStyle) hideStyle.remove();
        isInitialized = false;

        if (isDesktop()) {
            // 延遲一小會再初始化，避免連續觸發
            setTimeout(initScrollbars, 100);
        }
    }

    initScrollbars();
    window.addEventListener('resize', handleResize);
})();