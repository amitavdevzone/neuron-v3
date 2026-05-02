import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const pages = import.meta.glob('../pages/**/*.tsx');

/**
 * Wraps Laravel's resolvePageComponent so the return type matches Inertia v3's
 * {@link import('@inertiajs/react').default createInertiaApp} `resolve` callback
 * (Vite's `import.meta.glob` inference otherwise produces a nested-Promise union).
 */
export function resolveInertiaPage(name: string): Promise<ResolvedComponent> {
    return resolvePageComponent(
        `../pages/${name}.tsx`,
        pages,
    ) as Promise<ResolvedComponent>;
}
