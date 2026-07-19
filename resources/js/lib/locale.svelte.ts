import { page } from '@inertiajs/svelte';
import { translations, type Locale } from '@/lib/translations';

export type { Locale };

export type LocaleState = {
    locale: {
        value: Locale;
    };
    updateLocale: (value: Locale) => void;
};

const locale = $state<{ value: Locale }>({ value: 'ar' });

export function initializeLocale(): void {
    const serverLocale = page.props.locale as Locale | undefined;
    const stored = typeof window !== 'undefined' ? localStorage.getItem('locale') : null;

    if (stored === 'ar' || stored === 'en') {
        locale.value = stored;
    } else if (serverLocale === 'ar' || serverLocale === 'en') {
        locale.value = serverLocale;
    }

    applyLocale(locale.value);
}

function applyLocale(value: Locale): void {
    if (typeof document === 'undefined') return;
    document.documentElement.setAttribute('dir', value === 'ar' ? 'rtl' : 'ltr');
    document.documentElement.setAttribute('lang', value);
}

export function updateLocale(value: Locale): void {
    locale.value = value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('locale', value);
    }
    applyLocale(value);
}

export function t(key: string): string {
    return translations[locale.value]?.[key] ?? translations.en[key] ?? key;
}

export function localizedName(item: { name: string; name_en?: string } | null | undefined): string {
    if (!item) return '';
    if (locale.value === 'en' && item.name_en) return item.name_en;
    return item.name;
}

export function localeState(): LocaleState {
    return {
        locale,
        updateLocale,
    };
}
