import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { applySavedAccountLoginFlash } from '@/lib/saved-login-accounts';
import type { SharedData } from '@/types';

const processedPayloadKeys = new Set<string>();

export function SavedAccountLoginSync() {
    const page = usePage<SharedData>();
    const payload = page.props.flash?.saved_account_login ?? null;

    useEffect(() => {
        if (!payload) {
            return;
        }

        const dedupeKey = JSON.stringify(payload);
        if (processedPayloadKeys.has(dedupeKey)) {
            return;
        }

        processedPayloadKeys.add(dedupeKey);
        applySavedAccountLoginFlash(payload);
    }, [payload]);

    return null;
}
