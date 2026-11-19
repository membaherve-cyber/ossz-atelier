/*!
 * OSSZ ATELIER — app.js (mobile-first UI layer, vanilla, no dependencies)
 * --------------------------------------------------------------------------
 * Loaded with `defer` from layouts/app. Provides:
 *   1. `js` class on <html> (lets CSS distinguish JS-enabled clients).
 *   2. NProgress-style top progress bar for in-app navigations and form
 *      submits (opt out per form with a `data-no-progress` attribute).
 * Fully respects prefers-reduced-motion. No Alpine coupling.
 */
(function () {
    'use strict';

    // 1. JS capability flag (before anything can fail).
    document.documentElement.classList.add('js');

    var bar = null;
    var trickle = null;
    var resetTimer = null;
    var reduced = !!(window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    /** Create (once) and return the top progress bar element. */
    function ensureBar() {
        if (bar !== null) {
            return bar;
        }
        bar = document.createElement('div');
        bar.id = 'nav-progress';
        bar.setAttribute('aria-hidden', 'true');
        bar.style.position = 'fixed';
        bar.style.top = '0';
        bar.style.left = '0';
        bar.style.height = '3px';
        bar.style.width = '0';
        bar.style.background = '#7c5cbf';
        bar.style.zIndex = '80';
        bar.style.borderRadius = '0 2px 2px 0';
        bar.style.transition = 'width .25s ease, opacity .3s ease';
        bar.style.pointerEvents = 'none';
        if (document.body) {
            document.body.appendChild(bar);
        }
        return bar;
    }

    /** Start (or restart) the progress bar at 30% and trickle toward 70%. */
    function show() {
        var el = ensureBar();
        if (trickle !== null) {
            clearInterval(trickle);
            trickle = null;
        }
        if (resetTimer !== null) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
        if (reduced) {
            // No animation: jump straight to 60% and sit there.
            el.style.transition = 'none';
            el.style.opacity = '1';
            el.style.width = '60%';
            return;
        }
        el.style.transition = 'width .25s ease, opacity .3s ease';
        el.style.opacity = '1';
        el.style.width = '30%';
        trickle = setInterval(function () {
            var current = parseFloat(el.style.width) || 0;
            if (current < 70) {
                el.style.width = Math.min(70, current + Math.random() * 4) + '%';
            }
        }, 250);
    }

    /** Complete: fill to 100%, fade out, then reset invisibly. */
    function done() {
        var el = ensureBar();
        if (trickle !== null) {
            clearInterval(trickle);
            trickle = null;
        }
        if (reduced) {
            el.style.transition = 'none';
            el.style.width = '100%';
            el.style.opacity = '0';
            resetTimer = setTimeout(function () {
                el.style.width = '0';
                el.style.opacity = '1';
            }, 300);
            return;
        }
        el.style.transition = 'width .25s ease, opacity .3s ease';
        el.style.width = '100%';
        el.style.opacity = '0';
        resetTimer = setTimeout(function () {
            el.style.width = '0';
            el.style.opacity = '1';
        }, 300);
    }

    /** Instantly reset (page shown from cache, bfcache restore, etc.). */
    function reset() {
        if (bar === null) {
            return;
        }
        if (trickle !== null) {
            clearInterval(trickle);
            trickle = null;
        }
        if (resetTimer !== null) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
        bar.style.transition = 'none';
        bar.style.width = '0';
        bar.style.opacity = '1';
    }

    // Inject the bar as soon as the DOM is ready (defer ⇒ readyState is
    // "interactive" here, so the listener below still fires first).
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureBar);
    } else {
        ensureBar();
    }

    // Same-origin link clicks (capture phase, before anything can stop
    // them): new tab / download / anchor / modifier-key clicks are ignored.
    document.addEventListener('click', function (e) {
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a) {
            return;
        }
        if (a.href
            && a.href.indexOf(location.origin) === 0
            && !a.target
            && !a.hasAttribute('download')
            && a.getAttribute('href') !== '#'
            && !e.metaKey
            && !e.ctrlKey
            && !e.shiftKey
            && !e.altKey
            && a.href.indexOf('#') === -1) {
            show();
        }
    }, true);

    // The browser is about to paint a new document: finish the bar (it
    // disappears instantly on the new page regardless).
    window.addEventListener('beforeunload', done);

    // Fresh paint (or bfcache restore, e.g. the back button): make sure no
    // leftover bar state survives.
    window.addEventListener('pageshow', reset);

    // Form submits get the bar too — opt out with data-no-progress.
    document.addEventListener('submit', function (e) {
        if (!e.target.hasAttribute('data-no-progress')) {
            show();
        }
    }, true);
})();
