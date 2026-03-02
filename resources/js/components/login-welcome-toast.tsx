import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import type { SharedData } from '@/types';

const displayedToastKeys = new Set<string>();

export function LoginWelcomeToast() {
    const page = usePage<SharedData>();
    const payload = page.props.flash?.login_welcome_toast ?? null;

    useEffect(() => {
        if (!payload) {
            return;
        }

        if (displayedToastKeys.has(payload.key)) {
            return;
        }

        displayedToastKeys.add(payload.key);

        toast.success(payload.title, {
            description: payload.description,
        });
    }, [payload]);

    return <Toaster position="top-center" closeButton />;
}
