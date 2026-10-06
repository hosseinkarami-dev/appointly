import alertify from 'alertifyjs';
import feather from 'feather-icons';
import Swal from 'sweetalert2';
import 'alertifyjs/build/css/alertify.css';
import 'alertifyjs/build/css/themes/default.css';

window.alertify = alertify;
alertify.defaults.transition = 'slide';
alertify.defaults.notifier.position = 'top-center';
alertify.defaults.notifier.delay = 4.5;
alertify.defaults.notifier.dismissOnClick = true;
alertify.defaults.theme.ok = 'alertify-button alertify-button-ok';
alertify.defaults.theme.cancel = 'alertify-button alertify-button-cancel';

const replaceIcons = () => {
    document.querySelectorAll('i[data-feather]').forEach((iconElement) => {
        const iconName = iconElement.getAttribute('data-feather');
        const icon = feather.icons[iconName];
        const parent = iconElement.parentNode;

        if (!icon || !parent || !iconElement.isConnected) return;

        const attributes = [...iconElement.attributes].reduce((result, attribute) => {
            if (attribute.name !== 'data-feather') {
                result[attribute.name] = attribute.value;
            }

            return result;
        }, { 'stroke-width': 1.8 });
        const template = document.createElement('template');

        template.innerHTML = icon.toSvg(attributes).trim();

        const svgElement = template.content.firstElementChild;

        if (svgElement?.namespaceURI !== 'http://www.w3.org/2000/svg' || iconElement.parentNode !== parent) return;

        parent.replaceChild(svgElement, iconElement);
    });
};

let iconRefreshFrame = null;
let iconObserver = null;

const queueIconRefresh = () => {
    if (iconRefreshFrame !== null) return;

    iconRefreshFrame = requestAnimationFrame(() => {
        iconRefreshFrame = null;
        replaceIcons();
        initializeAlerts();
    });
};

const observeFeatherIcons = () => {
    if (iconObserver || ! document.documentElement) return;

    iconObserver = new MutationObserver((mutations) => {
        const hasNewFeatherIcon = mutations.some(({ addedNodes }) => [...addedNodes].some((node) => {
            if (!(node instanceof Element)) return false;

            return node.matches('i[data-feather]') || node.querySelector('i[data-feather]');
        }));

        if (hasNewFeatherIcon) queueIconRefresh();
    });

    iconObserver.observe(document.documentElement, { childList: true, subtree: true });
};

const enhanceConfirmationElement = (element) => {
    const confirmationAttribute = [...element.attributes].find(({ name }) => name.startsWith('wire:confirm'));
    const message = (confirmationAttribute?.value || '').replaceAll('\\n', '\n') || 'Are you sure?';
    const shouldPrompt = confirmationAttribute?.name === 'wire:confirm.prompt';
    const [question, expected] = shouldPrompt ? message.split('|') : [message, null];

    element.__livewire_confirm = async (action, instead) => {
        const result = await Swal.fire({
            title: shouldPrompt ? question : 'Confirm this action',
            text: shouldPrompt ? `Type “${expected}” to continue.` : message,
            icon: 'warning',
            input: shouldPrompt ? 'text' : undefined,
            inputPlaceholder: shouldPrompt ? expected : undefined,
            inputValidator: shouldPrompt ? (value) => value === expected ? undefined : 'The confirmation text does not match.' : undefined,
            showCancelButton: true,
            confirmButtonText: shouldPrompt ? 'Yes, continue' : 'Yes, remove',
            cancelButtonText: 'Keep it',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                popup: 'appointly-swal-popup',
                title: 'appointly-swal-title',
                htmlContainer: 'appointly-swal-message',
                input: 'appointly-swal-input',
                actions: 'appointly-swal-actions',
                confirmButton: 'appointly-swal-confirm',
                cancelButton: 'appointly-swal-cancel',
            },
        });

        result.isConfirmed ? action() : instead();
    };
};

const enhanceExistingConfirmations = () => {
    document.querySelectorAll('[wire\\:confirm], [wire\\:confirm\\.prompt]').forEach(enhanceConfirmationElement);
};

const registerLivewireHooks = () => {
    if (! window.Livewire || window.Livewire.appointlyIconsHooked) return;

    window.Livewire.appointlyIconsHooked = true;
    window.Livewire.hook('directive.init', ({ el, directive }) => {
        if (directive.name !== 'confirm') return;

        enhanceConfirmationElement(el);
    });
    window.Livewire.hook('morph.updated', queueIconRefresh);
    window.Livewire.hook('morph.added', queueIconRefresh);
};

const initializeAlerts = () => {
    document.querySelectorAll('[data-alertify-success], [data-alertify-error]').forEach((element) => {
        if (element.dataset.alertReady === 'true') return;

        const message = element.dataset.alertifySuccess || element.dataset.alertifyError;
        element.dataset.alertifySuccess ? alertify.success(message) : alertify.error(message);
        element.remove();
    });
};

const syncSidebarLinks = () => {
    const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
    const isDark = document.documentElement.classList.contains('dark');
    const activeClasses = ['bg-violet-100', 'text-violet-900', 'dark:bg-violet-400/20', 'dark:text-violet-200'];
    const inactiveClasses = ['text-[#171323]/55', 'dark:text-white/55'];

    document.querySelectorAll('aside a[href]:not([aria-label]), [data-workspace-nav] a[href]').forEach((link) => {
        const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '') || '/';
        const active = currentPath === linkPath || (linkPath !== '/workspace' && currentPath.startsWith(`${linkPath}/`));

        link.classList.remove(...activeClasses, ...inactiveClasses, 'bg-[#171323]/8', 'text-[#171323]', 'dark:bg-white/12', 'dark:text-white');
        link.classList.toggle('bg-violet-100', active && ! isDark);
        link.classList.toggle('text-violet-900', active && ! isDark);
        link.classList.toggle('dark:bg-violet-400/20', active && isDark);
        link.classList.toggle('dark:text-violet-200', active && isDark);
        link.classList.toggle('text-[#171323]/55', ! active);
        link.classList.toggle('dark:text-white/55', ! active);

        if (active) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
};

const initializeLandingNavigation = () => {
    const navigation = document.querySelector('[data-landing-nav]');

    if (! navigation || navigation.dataset.scrollReady === 'true') return;

    const updateNavigation = () => {
        navigation.classList.toggle('is-scrolled', window.scrollY > 24);
    };

    navigation.dataset.scrollReady = 'true';
    window.addEventListener('scroll', updateNavigation, { passive: true });
    updateNavigation();
};

const applySavedTheme = () => {
    const savedTheme = localStorage.getItem('appointly-theme');
    const preferredTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    document.documentElement.classList.toggle('dark', preferredTheme === 'dark');
};

applySavedTheme();

const syncThemeControls = () => {
    const isDark = document.documentElement.classList.contains('dark');

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        button.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        const label = button.querySelector('[data-theme-label]');
        if (label) label.textContent = isDark ? 'Light' : 'Dark';
        const icon = button.querySelector('[data-theme-icon]');
        if (icon) {
            icon.outerHTML = `<i data-theme-icon data-feather="${isDark ? 'sun' : 'moon'}" class="${icon.getAttribute('class') || ''}"></i>`;
        }
    });
    queueIconRefresh();
};

const initializeThemeControls = () => {
    syncThemeControls();
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');

    if (! toggle) return;

    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    localStorage.setItem('appointly-theme', nextTheme);
    applySavedTheme();
    syncThemeControls();
    syncSidebarLinks();
});

const bootApp = () => {
    observeFeatherIcons();
    queueIconRefresh();
    initializeThemeControls();
    initializeAlerts();
    enhanceExistingConfirmations();
    syncSidebarLinks();
    initializeLandingNavigation();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootApp, { once: true });
} else {
    bootApp();
}

document.addEventListener('livewire:navigated', () => {
    applySavedTheme();
    observeFeatherIcons();
    queueIconRefresh();
    initializeThemeControls();
    initializeAlerts();
    enhanceExistingConfirmations();
    syncSidebarLinks();
    initializeLandingNavigation();
});

document.addEventListener('livewire:navigating', applySavedTheme);

document.addEventListener('livewire:updated', () => {
    queueIconRefresh();
});

document.addEventListener('livewire:init', registerLivewireHooks);
document.addEventListener('livewire:initialized', () => {
    registerLivewireHooks();
    enhanceExistingConfirmations();
});
registerLivewireHooks();

let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    document.querySelectorAll('[data-install-app]').forEach((button) => {
        button.hidden = false;
    });
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    document.querySelectorAll('[data-install-app]').forEach((button) => {
        button.hidden = true;
    });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-install-app]');

    if (! button || ! deferredInstallPrompt) return;

    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    button.hidden = true;
});

const updateConnectionState = () => {
    document.querySelectorAll('[data-connection-status]').forEach((status) => {
        status.textContent = navigator.onLine ? 'Online' : 'Offline mode';
        status.classList.toggle('text-emerald-600', navigator.onLine);
        status.classList.toggle('text-amber-600', ! navigator.onLine);
    });
};

window.addEventListener('online', updateConnectionState);
window.addEventListener('offline', updateConnectionState);
window.addEventListener('load', updateConnectionState);

window.addEventListener('load', () => {
    if (! ('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' })
        .then((registration) => registration.update())
        .catch(() => {});
});
